<?php

namespace App\Jobs;

use App\Ai\Agents\HolidayPdfExtractionAgent;
use App\Models\HolidayImportBatch;
use App\Services\Holidays\DetectedHolidayGridRow;
use App\Services\Holidays\HolidayImportService;
use App\Services\Holidays\HolidayPdfGridExtractor;
use App\Support\AuditLogger;
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
    public function handle(
        HolidayImportService $imports,
        ?HolidayPdfGridExtractor $gridExtractor = null,
        ?AuditLogger $auditLogger = null,
    ): void {
        $auditLogger ??= app(AuditLogger::class);
        $gridExtractor ??= app(HolidayPdfGridExtractor::class);
        $batch = HolidayImportBatch::query()
            ->with('source')
            ->findOrFail($this->batchId);

        if ($batch->source->file_path === null) {
            $imports->markFailed($batch, 'The source does not have a stored PDF file.');
            $auditLogger->logSystem(
                action: 'pdf_parse_completed',
                entityType: 'holiday_import_batch',
                entityId: $batch->id,
                newValues: ['status' => 'rejected', 'failure_reason' => 'The source does not have a stored PDF file.'],
            );

            return;
        }

        $model = config('ai.holiday_pdf_extraction_model', 'gemini-2.5-flash-lite');
        $agent = new HolidayPdfExtractionAgent;
        $response = $agent->prompt(
            prompt: $this->prompt($batch),
            attachments: [
                Document::fromStorage($batch->source->file_path),
            ],
            provider: Lab::Gemini,
            model: $model,
            timeout: $this->timeout,
        );
        $responsePayload = $response->toArray();
        $gridRows = $gridExtractor->extract($batch->source->file_path, is_array($responsePayload['rows'] ?? null) ? $responsePayload['rows'] : []);

        if (! $this->hasUsableGridEvidence($gridRows)) {
            $responsePayload['grid_extraction_error'] = 'Code-owned checkmark grid extraction did not produce usable state applicability evidence.';

            $batch->update([
                'ai_raw_response' => $responsePayload,
            ]);

            $imports->markFailed($batch, 'Unable to extract state applicability from the PDF checkmark grid. No holiday rows were imported because state_codes must come from code-owned grid detection.');
            $auditLogger->logSystem(
                action: 'pdf_parse_completed',
                entityType: 'holiday_import_batch',
                entityId: $batch->id,
                newValues: [
                    'status' => 'rejected',
                    'failure_reason' => $batch->fresh()?->failure_reason,
                ],
            );

            return;
        }

        $responsePayload = $this->mergeGridDetection(
            $responsePayload,
            $gridRows,
        );

        $batch->update([
            'ai_raw_response' => $responsePayload,
        ]);

        $imports->completePendingBatch($batch, $this->rowsFromResponse($responsePayload));
        $auditLogger->logSystem(
            action: 'pdf_parse_completed',
            entityType: 'holiday_import_batch',
            entityId: $batch->id,
            newValues: [
                'status' => $batch->fresh()?->status,
                'total_rows' => $batch->fresh()?->total_rows,
                'valid_rows' => $batch->fresh()?->valid_rows,
                'invalid_rows' => $batch->fresh()?->invalid_rows,
            ],
        );
    }

    public function failed(Throwable $exception): void
    {
        $batch = HolidayImportBatch::query()->find($this->batchId);

        if ($batch !== null) {
            app(HolidayImportService::class)->markFailed($batch, $exception->getMessage());
            app(AuditLogger::class)->logSystem(
                action: 'pdf_parse_completed',
                entityType: 'holiday_import_batch',
                entityId: $batch->id,
                newValues: ['status' => 'rejected', 'failure_reason' => $exception->getMessage()],
            );
        }
    }

    private function prompt(HolidayImportBatch $batch): string
    {
        return "Extract text metadata for public holiday rows for source year {$batch->year}. Do not extract or infer state applicability; state checkmarks are processed separately by code.";
    }

    /**
     * @param  array<string, mixed>  $response
     * @param  array<int, DetectedHolidayGridRow>  $gridRows
     * @return array<string, mixed>
     */
    private function mergeGridDetection(array $response, array $gridRows): array
    {
        $rows = is_array($response['rows'] ?? null) ? $response['rows'] : [];

        $response['rows'] = array_values(array_map(function (mixed $row, int $index) use ($gridRows): array {
            $row = is_array($row) ? $row : [];
            $rowNumber = (int) ($row['row_number'] ?? $index + 1);
            $gridRow = $gridRows[$rowNumber] ?? new DetectedHolidayGridRow(
                rowNumber: $rowNumber,
                checkedColumns: [],
                uncheckedColumns: [],
                uncertainColumns: HolidayPdfGridExtractor::STATE_CODES,
                confidence: 0.0,
                warnings: ['No code-owned grid detection was available for this AI text row.'],
            );

            $checkedColumns = $this->stateCodeList($gridRow->checkedColumns);
            $uncheckedColumns = $this->stateCodeList($gridRow->uncheckedColumns);
            $uncertainColumns = $this->stateCodeList($gridRow->uncertainColumns);
            $warnings = array_values(array_unique(array_merge(
                $this->stringList($row['warnings'] ?? []),
                $gridRow->warnings,
            )));

            if (array_intersect($checkedColumns, $uncheckedColumns) !== []) {
                $warnings[] = 'Grid detection listed a column as both checked and unchecked; unchecked wins.';
                $checkedColumns = array_values(array_diff($checkedColumns, $uncheckedColumns));
            }

            if ($uncertainColumns !== []) {
                $warnings[] = 'Some state columns are uncertain: '.implode(', ', $uncertainColumns);
            }

            $source = is_array($row['source'] ?? null) ? $row['source'] : [];
            $rawText = (string) ($source['raw_row_text'] ?? '');

            return [
                'row_number' => $rowNumber,
                'year' => $row['year'] ?? null,
                'name' => $row['name'] ?? null,
                'date' => $row['date'] ?? null,
                'day_name' => $row['day_name'] ?? null,
                'marker' => $row['marker'] ?? null,
                'scope' => $this->scopeFromMarker($row['marker'] ?? null, $row['scope'] ?? null),
                'checked_columns' => $checkedColumns,
                'unchecked_columns' => $uncheckedColumns,
                'uncertain_columns' => $uncertainColumns,
                'state_codes' => $checkedColumns,
                'type' => $this->typeFromMarker($row['marker'] ?? null),
                'is_subject_to_change' => (bool) ($row['is_subject_to_change'] ?? false)
                    || str_contains((string) ($row['name'] ?? ''), '*')
                    || str_contains($rawText, '*'),
                'source' => $row['source'] ?? null,
                'warnings' => $warnings,
                'confidence' => min($this->confidence($row['confidence'] ?? null), $gridRow->confidence),
            ];
        }, $rows, array_keys($rows)));

        return $response;
    }

    /**
     * @param  array<int, DetectedHolidayGridRow>  $gridRows
     */
    private function hasUsableGridEvidence(array $gridRows): bool
    {
        if ($gridRows === []) {
            return false;
        }

        foreach ($gridRows as $gridRow) {
            if ($gridRow->checkedColumns !== [] || $gridRow->uncheckedColumns !== []) {
                return true;
            }
        }

        return false;
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
                    'state_codes' => is_array($payload['state_codes'] ?? null)
                        ? implode(',', $payload['state_codes'])
                        : ($payload['state_codes'] ?? ($payload['state_code'] ?? null)),
                    'name' => $payload['name'] ?? null,
                    'date' => $payload['date'] ?? null,
                    'scope' => $payload['scope'] ?? null,
                    'type' => $payload['type'] ?? null,
                    'is_subject_to_change' => $payload['is_subject_to_change'] ?? false,
                    'source_note' => $payload['source_note'] ?? $this->sourceNote($payload['source'] ?? null),
                ],
                'errors' => [],
                'warnings' => is_array($payload['warnings'] ?? null) ? $payload['warnings'] : [],
                'confidence' => $payload['confidence'] ?? null,
            ];
        }, $rows, array_keys($rows)));
    }

    private function sourceNote(mixed $source): ?string
    {
        if (! is_array($source)) {
            return null;
        }

        $parts = array_filter([
            $source['table_title'] ?? null,
            isset($source['page_number']) ? 'Page '.$source['page_number'] : null,
            $source['raw_marker'] ?? null,
            $source['raw_row_text'] ?? null,
        ], fn (mixed $part): bool => is_string($part) && trim($part) !== '');

        return $parts === [] ? null : implode(' | ', $parts);
    }

    private function scopeFromMarker(mixed $marker, mixed $scope): string
    {
        return match ($marker) {
            'P' => 'federal',
            'N' => 'state',
            'P/N' => 'federal_and_state',
            default => in_array($scope, ['federal', 'state', 'federal_and_state'], true) ? $scope : 'state',
        };
    }

    private function typeFromMarker(mixed $marker): string
    {
        return match ($marker) {
            'P' => 'federal',
            'N' => 'state',
            default => 'custom',
        };
    }

    /**
     * @return list<string>
     */
    private function stateCodeList(mixed $value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn (mixed $code): string => strtoupper(trim((string) $code)),
            $value,
        ), fn (string $code): bool => in_array($code, HolidayPdfGridExtractor::STATE_CODES, true))));
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (mixed $warning): string => trim((string) $warning),
            $value,
        )));
    }

    private function confidence(mixed $confidence): float
    {
        if (! is_numeric($confidence)) {
            return 0.0;
        }

        return max(0.0, min(1.0, (float) $confidence));
    }
}
