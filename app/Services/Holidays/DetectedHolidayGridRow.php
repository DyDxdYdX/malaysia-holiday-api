<?php

namespace App\Services\Holidays;

class DetectedHolidayGridRow
{
    /**
     * @param  list<string>  $checkedColumns
     * @param  list<string>  $uncheckedColumns
     * @param  list<string>  $uncertainColumns
     * @param  list<string>  $warnings
     */
    public function __construct(
        public int $rowNumber,
        public array $checkedColumns,
        public array $uncheckedColumns,
        public array $uncertainColumns,
        public float $confidence,
        public array $warnings = [],
    ) {}

    /**
     * @return array{
     *     row_number: int,
     *     checked_columns: list<string>,
     *     unchecked_columns: list<string>,
     *     uncertain_columns: list<string>,
     *     confidence: float,
     *     warnings: list<string>
     * }
     */
    public function toArray(): array
    {
        return [
            'row_number' => $this->rowNumber,
            'checked_columns' => $this->checkedColumns,
            'unchecked_columns' => $this->uncheckedColumns,
            'uncertain_columns' => $this->uncertainColumns,
            'confidence' => $this->confidence,
            'warnings' => $this->warnings,
        ];
    }
}
