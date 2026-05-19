<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Support\AuditLogger;
use App\Support\HolidayCalendarBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HolidayController extends Controller
{
    public function calendar(Request $request, HolidayCalendarBuilder $calendarBuilder): View
    {
        $year = $request->integer('year');
        $resolvedYear = $year > 0 ? $year : Carbon::now()->year;
        $stateCode = strtoupper(trim($request->string('state_code')->toString()));
        $scope = trim($request->string('scope')->toString());

        $holidays = Holiday::query()
            ->where('year', $resolvedYear)
            ->when($stateCode !== '', fn ($query) => $query->where('state_code', $stateCode))
            ->when($scope !== '', fn ($query) => $query->where('scope', $scope))
            ->orderBy('date')
            ->orderBy('name')
            ->get();

        return view('holidays.calendar', [
            'title' => __('Holiday Calendar'),
            'subtitle' => __('Review holiday records in a year-based calendar view.'),
            'filters' => [
                'year' => (string) $resolvedYear,
                'state_code' => $stateCode,
                'scope' => $scope,
            ],
            'months' => $calendarBuilder->build($resolvedYear, $holidays),
            'isAdminView' => true,
            'hasAnyHoliday' => $holidays->isNotEmpty(),
            'formAction' => route('admin.holidays.calendar'),
        ]);
    }

    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $year = $request->integer('year');
        $stateCode = strtoupper(trim($request->string('state_code')->toString()));
        $scope = trim($request->string('scope')->toString());

        return view('admin.holidays.index', [
            'holidays' => Holiday::query()
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($nested) use ($search) {
                        $nested->where('name', 'like', "%{$search}%")
                            ->orWhere('state_code', 'like', "%{$search}%")
                            ->orWhere('source_note', 'like', "%{$search}%")
                            ->orWhere('date', 'like', "%{$search}%");
                    });
                })
                ->when($year > 0, fn ($query) => $query->where('year', $year))
                ->when($stateCode !== '', fn ($query) => $query->where('state_code', $stateCode))
                ->when($scope !== '', fn ($query) => $query->where('scope', $scope))
                ->latest('date')
                ->paginate(20)
                ->withQueryString(),
            'filters' => [
                'q' => $search,
                'year' => $year > 0 ? (string) $year : '',
                'state_code' => $stateCode,
                'scope' => $scope,
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.holidays.create');
    }

    public function edit(Holiday $holiday): View
    {
        return view('admin.holidays.edit', [
            'holiday' => $holiday,
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'state_code' => ['required', 'string', 'max:10'],
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'scope' => ['required', Rule::in(['federal', 'state', 'custom'])],
            'type' => ['required', Rule::in(['federal', 'state', 'replacement', 'additional', 'custom'])],
            'is_subject_to_change' => ['nullable', 'boolean'],
            'source_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $date = Carbon::parse($validated['date']);

        $holiday = Holiday::query()->create([
            ...$validated,
            'state_code' => strtoupper($validated['state_code']),
            'date' => $date->toDateString(),
            'day_name' => $date->format('l'),
            'is_subject_to_change' => $request->boolean('is_subject_to_change'),
            'status' => 'published',
        ]);
        $auditLogger->logFromRequest(
            request: $request,
            action: 'holiday_created',
            entityType: 'holiday',
            entityId: $holiday->id,
            newValues: $auditLogger->modelSnapshot($holiday),
        );

        return redirect()
            ->route('admin.holidays.index')
            ->with('status', 'Manual holiday added.');
    }

    public function update(Request $request, Holiday $holiday, AuditLogger $auditLogger): RedirectResponse
    {
        $oldValues = $holiday->toArray();
        $validated = $request->validate([
            'year' => ['required', 'integer', 'between:2000,2100'],
            'state_code' => ['required', 'string', 'max:10'],
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'scope' => ['required', Rule::in(['federal', 'state', 'custom'])],
            'type' => ['required', Rule::in(['federal', 'state', 'replacement', 'additional', 'custom'])],
            'is_subject_to_change' => ['nullable', 'boolean'],
            'source_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $date = Carbon::parse($validated['date']);

        $holiday->update([
            ...$validated,
            'state_code' => strtoupper($validated['state_code']),
            'date' => $date->toDateString(),
            'day_name' => $date->format('l'),
            'is_subject_to_change' => $request->boolean('is_subject_to_change'),
            'status' => 'confirmed',
        ]);
        $auditLogger->logFromRequest(
            request: $request,
            action: 'holiday_updated',
            entityType: 'holiday',
            entityId: $holiday->id,
            oldValues: $oldValues,
            newValues: $holiday->fresh()?->toArray(),
        );

        return redirect()
            ->route('admin.batches.show', $holiday->holiday_import_batch_id)
            ->with('status', 'Holiday updated.');
    }

    public function reject(Request $request, Holiday $holiday, AuditLogger $auditLogger): RedirectResponse
    {
        $oldValues = $holiday->toArray();
        $holiday->update(['status' => 'cancelled']);
        $auditLogger->logFromRequest(
            request: $request,
            action: 'holiday_deleted',
            entityType: 'holiday',
            entityId: $holiday->id,
            oldValues: $oldValues,
            newValues: $holiday->fresh()?->toArray(),
        );

        return redirect()
            ->route('admin.batches.show', $holiday->holiday_import_batch_id)
            ->with('status', 'Holiday rejected.');
    }
}
