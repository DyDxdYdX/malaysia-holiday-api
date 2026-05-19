<?php

namespace App\Services\Holidays;

class HolidayImportTemplate
{
    public const HEADERS = [
        'year',
        'state_codes',
        'name',
        'date',
        'scope',
        'type',
        'is_subject_to_change',
        'source_note',
    ];

    /**
     * @return list<array<string, string>>
     */
    public function sampleRows(int $year): array
    {
        return [
            [
                'year' => (string) $year,
                'state_codes' => 'SBH',
                'name' => 'Hari Jadi Yang di-Pertua Negeri Sabah',
                'date' => $year.'-03-30',
                'scope' => 'state',
                'type' => 'state',
                'is_subject_to_change' => 'false',
                'source_note' => 'JPM HKA '.$year,
            ],
            [
                'year' => (string) $year,
                'state_codes' => 'SBH',
                'name' => 'Pesta Kaamatan',
                'date' => $year.'-05-30',
                'scope' => 'state',
                'type' => 'state',
                'is_subject_to_change' => 'false',
                'source_note' => 'JPM HKA '.$year,
            ],
            [
                'year' => (string) $year,
                'state_codes' => 'JHR,KDH,KTN,MLK,NSN,PHG,PRK,PLS,PNG,SBH,SWK,SGR,TRG,KUL,LBN,PJY',
                'name' => 'Hari Kebangsaan',
                'date' => $year.'-08-31',
                'scope' => 'federal',
                'type' => 'federal',
                'is_subject_to_change' => 'false',
                'source_note' => 'JPM HKA '.$year,
            ],
        ];
    }
}
