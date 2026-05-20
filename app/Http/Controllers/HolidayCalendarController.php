<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Support\HolidayCalendarBuilder;
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

        return view('holidays.calendar', [
            'title' => __('Holiday Calendar'),
            'subtitle' => __('Browse published holidays for the selected year.'),
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
