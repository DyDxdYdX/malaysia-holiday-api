<?php

namespace App\Services\Holidays;

use App\Models\Holiday;
use App\Models\HolidayImportBatch;
use App\Models\HolidaySource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class HolidayImportService
{
    private const STATE_CODES = [
        'JHR',
        'KDH',
        'KTN',
        'MLK',
        'NSN',
        'PHG',
        'PRK',
        'PLS',
        'PNG',
        'SBH',
        'SWK',
        'SGR',
        'TRG',
        'KUL',
        'LBN',
        'PJY',
    ];

    private const SCOPES = ['federal', 'state', 'custom'];

    private const TYPES = ['federal', 'state', 'replacement', 'additional', 'custom'];

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function importRows(
        HolidaySource $source,
        array $rows,
        ?int $importedBy,
        string $importMethod,
        ?string $provider = null,
        ?string $model = null,
    ): HolidayImportBatch {
        return DB::transaction(function () use ($source, $rows, $importedBy, $importMethod, $provider, $model): HolidayImportBatch {
            $batch = $this->createBatch($source, $importedBy, $importMethod, $provider, $model, 'review_required');

            $this->storeRows($batch, $source, $rows);

            return $batch->refresh();
        });
    }

    public function createPendingBatch(
        HolidaySource $source,
        ?int $importedBy,
        string $importMethod,
        ?string $provider = null,
        ?string $model = null,
    ): HolidayImportBatch {
        return $this->createBatch($source, $importedBy, $importMethod, $provider, $model, 'draft');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function completePendingBatch(HolidayImportBatch $batch, array $rows): HolidayImportBatch
    {
        return DB::transaction(function () use ($batch, $rows): HolidayImportBatch {
            $batch->importRows()->delete();
            $batch->holidays()->delete();

            $this->storeRows($batch, $batch->source, $rows);

            return $batch->refresh();
        });
    }

    public function markFailed(HolidayImportBatch $batch, string $reason): void
    {
        $batch->update([
            'status' => 'rejected',
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    private function createBatch(
        HolidaySource $source,
        ?int $importedBy,
        string $importMethod,
        ?string $provider,
        ?string $model,
        string $status,
    ): HolidayImportBatch {
        return HolidayImportBatch::create([
            'holiday_source_id' => $source->id,
            'year' => $source->year,
            'status' => $status,
            'import_method' => $importMethod,
            'provider' => $provider,
            'model' => $model,
            'started_at' => now(),
            'total_rows' => 0,
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'warning_rows' => 0,
            'imported_by' => $importedBy,
            'imported_at' => now(),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function storeRows(HolidayImportBatch $batch, HolidaySource $source, array $rows): void
    {
        $validRows = 0;
        $invalidRows = 0;
        $warningRows = 0;

        foreach ($rows as $index => $row) {
            $rowNumber = (int) ($row['row_number'] ?? $index + 1);
            $rawPayload = $this->payloadArray($row['raw_payload'] ?? []);
            $normalizedPayload = $this->normalizePayload($this->payloadArray($row['normalized_payload'] ?? $rawPayload));
            $errors = array_values(array_filter([
                ...$this->stringList($row['errors'] ?? []),
                ...$this->validatePayload($normalizedPayload, $source),
            ]));
            $warnings = array_values(array_filter($this->stringList($row['warnings'] ?? [])));
            $confidence = isset($row['confidence']) && is_numeric($row['confidence']) ? (float) $row['confidence'] : null;

            if ($this->booleanValue($normalizedPayload['is_subject_to_change'] ?? false)) {
                $warnings[] = 'Holiday is marked as subject to change.';
            }

            $status = match (true) {
                $errors !== [] => 'invalid',
                $warnings !== [] => 'warning',
                default => 'valid',
            };

            $batch->importRows()->create([
                'row_number' => $rowNumber,
                'raw_payload' => $rawPayload,
                'normalized_payload' => $normalizedPayload,
                'status' => $status,
                'errors' => $errors,
                'warnings' => $warnings,
                'confidence' => $confidence,
            ]);

            if ($status === 'invalid') {
                $invalidRows++;

                continue;
            }

            $this->createHoliday($batch, $source, $normalizedPayload);
            $validRows++;

            if ($status === 'warning') {
                $warningRows++;
            }
        }

        $batch->update([
            'status' => 'review_required',
            'total_rows' => count($rows),
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
            'warning_rows' => $warningRows,
            'completed_at' => now(),
            'failed_at' => null,
            'failure_reason' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<string>
     */
    private function validatePayload(array $payload, HolidaySource $source): array
    {
        $errors = [];

        foreach (['year', 'state_codes', 'name', 'date', 'scope', 'type'] as $field) {
            if (! isset($payload[$field]) || trim((string) $payload[$field]) === '') {
                $errors[] = "Missing required value for {$field}.";
            }
        }

        if ($errors !== []) {
            return $errors;
        }

        if ((int) $payload['year'] !== $source->year) {
            $errors[] = 'Holiday year must match the source year.';
        }

        $stateCodes = $this->parseStateCodes((string) $payload['state_codes']);

        if ($stateCodes === []) {
            $errors[] = 'State codes are required.';
        }

        if (count(array_diff($stateCodes, self::STATE_CODES)) > 0) {
            $errors[] = 'One or more state codes are not supported.';
        }

        if (! $this->isDateString((string) $payload['date'])) {
            $errors[] = 'Date must use YYYY-MM-DD format.';
        }

        if (! in_array($payload['scope'], self::SCOPES, true)) {
            $errors[] = 'Scope is not supported.';
        }

        if (! in_array($payload['type'], self::TYPES, true)) {
            $errors[] = 'Holiday type is not supported.';
        }

        if ($errors === [] && $this->holidayExists($payload)) {
            $errors[] = 'Duplicate holiday record for year, date, and name.';
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createHoliday(HolidayImportBatch $batch, HolidaySource $source, array $payload): Holiday
    {
        $date = Carbon::createFromFormat('Y-m-d', (string) $payload['date']);

        $holiday = Holiday::create([
            'holiday_source_id' => $source->id,
            'holiday_import_batch_id' => $batch->id,
            'year' => (int) $payload['year'],
            'name' => $payload['name'],
            'date' => $date->toDateString(),
            'day_name' => $date->format('l'),
            'scope' => $payload['scope'],
            'type' => $payload['type'],
            'is_subject_to_change' => $this->booleanValue($payload['is_subject_to_change'] ?? false),
            'status' => 'draft',
            'source_note' => $payload['source_note'] ?? null,
        ]);

        $holiday->syncStateCodes($this->parseStateCodes((string) ($payload['state_codes'] ?? '')));

        return $holiday;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizePayload(array $payload): array
    {
        return [
            'year' => isset($payload['year']) ? (int) $payload['year'] : null,
            'state_codes' => isset($payload['state_codes']) ? trim((string) $payload['state_codes']) : null,
            'name' => isset($payload['name']) ? trim((string) $payload['name']) : null,
            'date' => isset($payload['date']) ? trim((string) $payload['date']) : null,
            'scope' => isset($payload['scope']) ? strtolower(trim((string) $payload['scope'])) : null,
            'type' => isset($payload['type']) ? strtolower(trim((string) $payload['type'])) : null,
            'is_subject_to_change' => $this->booleanValue($payload['is_subject_to_change'] ?? false),
            'source_note' => isset($payload['source_note']) && trim((string) $payload['source_note']) !== ''
                ? trim((string) $payload['source_note'])
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadArray(mixed $payload): array
    {
        return is_array($payload) ? $payload : [];
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(
            fn (mixed $item): string => trim((string) $item),
            $value
        ));
    }

    private function isDateString(string $date): bool
    {
        $parsed = Carbon::createFromFormat('Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    private function booleanValue(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function holidayExists(array $payload): bool
    {
        return Holiday::query()
            ->where('year', (int) $payload['year'])
            ->whereDate('date', (string) $payload['date'])
            ->where('name', $payload['name'])
            ->exists();
    }

    /**
     * @return list<string>
     */
    private function parseStateCodes(string $stateCodes): array
    {
        return collect(preg_split('/[\s,|]+/', strtoupper($stateCodes)) ?: [])
            ->map(fn (string $stateCode): string => trim($stateCode))
            ->filter(fn (string $stateCode): bool => $stateCode !== '')
            ->unique()
            ->values()
            ->all();
    }
}
