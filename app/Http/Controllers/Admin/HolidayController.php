<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Support\AuditLogger;
use App\Support\HolidayCalendarBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
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
                            ->orWhere('source_note', 'like', "%{$search}%")
                            ->orWhere('date', 'like', "%{$search}%")
                            ->orWhereHas('states', function ($stateQuery) use ($search): void {
                                $stateQuery->where('state_code', 'like', "%{$search}%");
                            });
                    });
                })
                ->when($year > 0, fn ($query) => $query->where('year', $year))
                ->when($stateCode !== '', function ($query) use ($stateCode): void {
                    $query->whereHas('states', function ($stateQuery) use ($stateCode): void {
                        $stateQuery->where('state_code', $stateCode);
                    });
                })
                ->when($scope !== '', fn ($query) => $query->where('scope', $scope))
                ->latest('date')
                ->with('states')
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
            'state_codes' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'scope' => ['required', Rule::in(['federal', 'state', 'custom'])],
            'type' => ['required', Rule::in(['federal', 'state', 'replacement', 'additional', 'custom'])],
            'is_subject_to_change' => ['nullable', 'boolean'],
            'source_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $date = Carbon::parse($validated['date']);
        $stateCodes = $this->parseStateCodes($validated['state_codes']);

        if ($stateCodes === []) {
            return back()->withErrors(['state_codes' => 'At least one state code is required.'])->withInput();
        }

        $holiday = Holiday::query()->create([
            'year' => $validated['year'],
            'name' => $validated['name'],
            'date' => $date->toDateString(),
            'day_name' => $date->format('l'),
            'scope' => $validated['scope'],
            'type' => $validated['type'],
            'is_subject_to_change' => $request->boolean('is_subject_to_change'),
            'source_note' => $validated['source_note'] ?? null,
            'status' => 'published',
        ]);
        $holiday->syncStateCodes($stateCodes);
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
            'state_codes' => ['required', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'scope' => ['required', Rule::in(['federal', 'state', 'custom'])],
            'type' => ['required', Rule::in(['federal', 'state', 'replacement', 'additional', 'custom'])],
            'is_subject_to_change' => ['nullable', 'boolean'],
            'source_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $date = Carbon::parse($validated['date']);
        $stateCodes = $this->parseStateCodes($validated['state_codes']);

        if ($stateCodes === []) {
            return back()->withErrors(['state_codes' => 'At least one state code is required.'])->withInput();
        }

        $holiday->update([
            'year' => $validated['year'],
            'name' => $validated['name'],
            'date' => $date->toDateString(),
            'day_name' => $date->format('l'),
            'scope' => $validated['scope'],
            'type' => $validated['type'],
            'is_subject_to_change' => $request->boolean('is_subject_to_change'),
            'source_note' => $validated['source_note'] ?? null,
            'status' => 'confirmed',
        ]);
        $holiday->syncStateCodes($stateCodes);
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

    /**
     * @return list<string>
     */
    private function parseStateCodes(string $stateCodes): array
    {
        return collect(preg_split('/[\s,|]+/', Str::upper($stateCodes)) ?: [])
            ->map(fn (string $stateCode): string => trim($stateCode))
            ->filter(fn (string $stateCode): bool => $stateCode !== '')
            ->unique()
            ->values()
            ->all();
    }
}
