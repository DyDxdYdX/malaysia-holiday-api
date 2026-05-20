<?php

namespace App\Services\Holidays;

use Illuminate\Support\Facades\Storage;
use JsonException;

class HolidayPdfGridExtractor
{
    /**
     * @var list<string>
     */
    public const STATE_CODES = [
        'KUL',
        'LBN',
        'PJY',
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
    ];

    /**
     * Extract state applicability from code-owned grid evidence, never from AI text.
     *
     * If the PDF-to-image/grid classifier is unavailable, return explicit uncertainty so
     * the import workflow rejects or flags the row instead of guessing all states.
     *
     * @param  list<array<string, mixed>>  $textRows
     * @return array<int, DetectedHolidayGridRow>
     */
    public function extract(?string $pdfPath, array $textRows): array
    {
        $sidecarRows = $this->sidecarRows($pdfPath);

        if ($sidecarRows !== []) {
            return $sidecarRows;
        }

        $detections = [];

        foreach ($textRows as $index => $row) {
            $rowNumber = (int) ($row['row_number'] ?? $index + 1);
            $detections[$rowNumber] = new DetectedHolidayGridRow(
                rowNumber: $rowNumber,
                checkedColumns: [],
                uncheckedColumns: [],
                uncertainColumns: self::STATE_CODES,
                confidence: 0.0,
                warnings: ['Code checkmark grid extraction did not produce cell-level evidence for this row.'],
            );
        }

        return $detections;
    }

    /**
     * @return array<int, DetectedHolidayGridRow>
     */
    private function sidecarRows(?string $pdfPath): array
    {
        if ($pdfPath === null) {
            return [];
        }

        $sidecarPath = preg_replace('/\.pdf$/i', '.grid.json', $pdfPath) ?? $pdfPath.'.grid.json';

        if (! Storage::exists($sidecarPath)) {
            return [];
        }

        try {
            $payload = json_decode(Storage::get($sidecarPath), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        $rows = is_array($payload['rows'] ?? null) ? $payload['rows'] : [];
        $detections = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $rowNumber = (int) ($row['row_number'] ?? $index + 1);
            $detections[$rowNumber] = new DetectedHolidayGridRow(
                rowNumber: $rowNumber,
                checkedColumns: $this->stateCodeList($row['checked_columns'] ?? []),
                uncheckedColumns: $this->stateCodeList($row['unchecked_columns'] ?? []),
                uncertainColumns: $this->stateCodeList($row['uncertain_columns'] ?? []),
                confidence: $this->confidence($row['confidence'] ?? null),
                warnings: $this->stringList($row['warnings'] ?? []),
            );
        }

        return $detections;
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
        ), fn (string $code): bool => in_array($code, self::STATE_CODES, true))));
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
