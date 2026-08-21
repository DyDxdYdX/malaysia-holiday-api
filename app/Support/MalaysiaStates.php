<?php

namespace App\Support;

class MalaysiaStates
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'JHR' => 'Johor',
            'KDH' => 'Kedah',
            'KTN' => 'Kelantan',
            'MLK' => 'Melaka',
            'NSN' => 'Negeri Sembilan',
            'PHG' => 'Pahang',
            'PRK' => 'Perak',
            'PLS' => 'Perlis',
            'PNG' => 'Pulau Pinang',
            'SBH' => 'Sabah',
            'SWK' => 'Sarawak',
            'SGR' => 'Selangor',
            'TRG' => 'Terengganu',
            'KUL' => 'Wilayah Persekutuan Kuala Lumpur',
            'LBN' => 'Wilayah Persekutuan Labuan',
            'PJY' => 'Wilayah Persekutuan Putrajaya',
        ];
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::options());
    }

    /**
     * @return array<string, array{label: string, codes: list<string>}>
     */
    public static function presets(): array
    {
        return [
            'all' => [
                'label' => 'All states',
                'codes' => self::codes(),
            ],
            'peninsular' => [
                'label' => 'Peninsular Malaysia',
                'codes' => ['JHR', 'KDH', 'KTN', 'MLK', 'NSN', 'PHG', 'PRK', 'PLS', 'PNG', 'SGR', 'TRG', 'KUL', 'PJY'],
            ],
            'east_malaysia' => [
                'label' => 'East Malaysia',
                'codes' => ['SBH', 'SWK'],
            ],
            'federal_territories' => [
                'label' => 'Federal Territories',
                'codes' => ['KUL', 'LBN', 'PJY'],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function presetCodes(string $key): array
    {
        return self::presets()[$key]['codes'] ?? [];
    }

    /**
     * @param  list<string>  $stateCodes
     */
    public static function isAll(array $stateCodes): bool
    {
        $normalized = collect($stateCodes)
            ->map(fn (string $stateCode): string => strtoupper(trim($stateCode)))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $allCodes = collect(self::codes())->sort()->values()->all();

        return $normalized === $allCodes;
    }
}
