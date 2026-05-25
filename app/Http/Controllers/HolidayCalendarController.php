<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Support\HolidayCalendarBuilder;
use App\Support\MalaysiaStates;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class HolidayCalendarController extends Controller
{
    public function __invoke(Request $request, HolidayCalendarBuilder $calendarBuilder): View
    {
        $year = $request->integer('year');
        $resolvedYear = $year > 0 ? $year : Carbon::now()->year;
        $month = $request->integer('month');
        $resolvedMonth = $month > 0 && $month <= 12 ? $month : 1;
        $stateCode = strtoupper(trim($request->string('state_code')->toString()));
        $scope = trim($request->string('scope')->toString());

        $holidays = Holiday::query()
            ->where('status', 'published')
            ->where('year', $resolvedYear)
            ->when($stateCode !== '', function ($query) use ($stateCode): void {
                $query->whereHas('states', function ($stateQuery) use ($stateCode): void {
                    $stateQuery->where('state_code', $stateCode);
                });
            })
            ->when($scope !== '', fn ($query) => $query->where('scope', $scope))
            ->orderBy('date')
            ->orderBy('name')
            ->with('states')
            ->get();

        $stateName = MalaysiaStates::options()[$stateCode] ?? '';

        if ($stateName !== '') {
            $title = __(':state Public Holidays :year Calendar', [
                'state' => $stateName,
                'year' => $resolvedYear,
            ]);
            $subtitle = __('Browse public holidays, state holidays, and long weekends in :state, Malaysia for :year.', [
                'state' => $stateName,
                'year' => $resolvedYear,
            ]);
        } else {
            $title = __('Malaysia Public Holidays :year Calendar', [
                'year' => $resolvedYear,
            ]);
            $subtitle = __('Browse national public holidays, state-level holidays, and federal holidays in Malaysia for :year.', [
                'year' => $resolvedYear,
            ]);
        }

        return view('holidays.calendar', [
            'title' => $title,
            'subtitle' => $subtitle,
            'filters' => [
                'year' => (string) $resolvedYear,
                'month' => (string) $resolvedMonth,
                'state_code' => $stateCode,
                'scope' => $scope,
            ],
            'month' => $calendarBuilder->buildMonth($resolvedYear, $resolvedMonth, $holidays),
            'isAdminView' => false,
            'hasAnyHoliday' => $holidays->isNotEmpty(),
            'formAction' => route('holidays.calendar'),
        ]);
    }
}
