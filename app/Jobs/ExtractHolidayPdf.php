<?php

namespace App\Jobs;

use App\Ai\Agents\HolidayPdfExtractionAgent;
use App\Models\HolidayImportBatch;
use App\Services\Holidays\HolidayImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Files\Document;
use Throwable;

class ExtractHolidayPdf implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;

    public int $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $batchId) {}

    /**
     * Execute the job.
     */
    public function handle(HolidayImportService $imports): void
    {
        $batch = HolidayImportBatch::query()
            ->with('source')
            ->findOrFail($this->batchId);

        if ($batch->source->file_path === null) {
            $imports->markFailed($batch, 'The source does not have a stored PDF file.');

            return;
        }

        $model = config('ai.holiday_pdf_extraction_model', 'gemini-2.5-flash-lite');
        $response = (new HolidayPdfExtractionAgent)->prompt(
            prompt: $this->prompt($batch),
            attachments: [
                Document::fromStorage($batch->source->file_path),
            ],
            provider: Lab::Gemini,
            model: $model,
            timeout: $this->timeout,
        );

        $imports->completePendingBatch($batch, $this->rowsFromResponse($response->toArray()));
    }

    public function failed(Throwable $exception): void
    {
        $batch = HolidayImportBatch::query()->find($this->batchId);

        if ($batch !== null) {
            app(HolidayImportService::class)->markFailed($batch, $exception->getMessage());
        }
    }

    private function prompt(HolidayImportBatch $batch): string
    {
        return "Extract public holiday rows for source year {$batch->year}. Return rows that match the configured structured output schema.";
    }

    /**
     * @param  array<string, mixed>  $response
     * @return list<array<string, mixed>>
     */
    private function rowsFromResponse(array $response): array
    {
        $rows = is_array($response['rows'] ?? null) ? $response['rows'] : [];

        return array_values(array_map(function (mixed $row, int $index): array {
            $payload = is_array($row) ? $row : [];

            return [
                'row_number' => (int) ($payload['row_number'] ?? $index + 1),
                'raw_payload' => $payload,
                'normalized_payload' => [
                    'year' => $payload['year'] ?? null,
                    'state_code' => $payload['state_code'] ?? null,
                    'name' => $payload['name'] ?? null,
                    'date' => $payload['date'] ?? null,
                    'scope' => $payload['scope'] ?? null,
                    'type' => $payload['type'] ?? null,
                    'is_subject_to_change' => $payload['is_subject_to_change'] ?? false,
                    'source_note' => $payload['source_note'] ?? null,
                ],
                'errors' => [],
                'warnings' => is_array($payload['warnings'] ?? null) ? $payload['warnings'] : [],
                'confidence' => $payload['confidence'] ?? null,
            ];
        }, $rows, array_keys($rows)));
    }
}
