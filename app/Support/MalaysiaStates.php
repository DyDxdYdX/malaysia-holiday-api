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
}
