<?php

namespace App\Support;

use App\Models\Holiday;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class HolidayCalendarBuilder
{
    /**
     * @param  Collection<int, Holiday>  $holidays
     * @return array<int, array{
     *     month_number: int,
     *     month_name: string,
     *     weeks: array<int, array<int, array{
     *         date: Carbon,
     *         in_month: bool,
     *         holidays: Collection<int, Holiday>
     *     }>>
     * }>
     */
    public function build(int $year, Collection $holidays): array
    {
        /** @var Collection<string, Collection<int, Holiday>> $holidaysByDate */
        $holidaysByDate = $holidays->groupBy(
            fn (Holiday $holiday): string => $holiday->date->toDateString()
        );

        $months = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthStart = Carbon::create($year, $month, 1)->startOfDay();
            $calendarStart = $monthStart->copy()->startOfWeek(Carbon::SUNDAY);
            $calendarEnd = $monthStart->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

            $weeks = [];
            $week = [];

            for ($cursor = $calendarStart->copy(); $cursor->lte($calendarEnd); $cursor->addDay()) {
                $day = $cursor->copy();
                $week[] = [
                    'date' => $day,
                    'in_month' => $day->month === $month,
                    'holidays' => $holidaysByDate->get($day->toDateString(), collect()),
                ];

                if (count($week) === 7) {
                    $weeks[] = $week;
                    $week = [];
                }
            }

            $months[] = [
                'month_number' => $month,
                'month_name' => $monthStart->format('F'),
                'weeks' => $weeks,
            ];
        }

        return $months;
    }
}
