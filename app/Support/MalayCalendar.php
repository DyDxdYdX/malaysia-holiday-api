<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;

class MalayCalendar
{
    /**
     * @return array<int, string>
     */
    public static function months(): array
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Mac',
            4 => 'April',
            5 => 'Mei',
            6 => 'Jun',
            7 => 'Julai',
            8 => 'Ogos',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Disember',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function days(): array
    {
        return [
            Carbon::SUNDAY => 'Ahad',
            Carbon::MONDAY => 'Isnin',
            Carbon::TUESDAY => 'Selasa',
            Carbon::WEDNESDAY => 'Rabu',
            Carbon::THURSDAY => 'Khamis',
            Carbon::FRIDAY => 'Jumaat',
            Carbon::SATURDAY => 'Sabtu',
        ];
    }

    public static function dateLabel(DateTimeInterface $date): string
    {
        $date = Carbon::parse($date);

        return $date->day.' '.self::months()[$date->month];
    }

    public static function dayName(DateTimeInterface $date): string
    {
        $date = Carbon::parse($date);

        return self::days()[$date->dayOfWeek];
    }

    public static function marker(string $scope): string
    {
        return in_array($scope, ['federal', 'federal_and_state'], true) ? 'P' : 'N';
    }
}
