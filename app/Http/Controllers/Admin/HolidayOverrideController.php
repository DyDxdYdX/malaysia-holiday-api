<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\HolidayOverride;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HolidayOverrideController extends Controller
{
    public function index(): View
    {
        return view('admin.overrides.index', [
            'overrides' => HolidayOverride::query()
                ->with(['holiday', 'approver'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        $selectedHoliday = null;
        $selectedHolidayId = request()->integer('holiday_id');

        if ($selectedHolidayId > 0) {
            $selectedHoliday = Holiday::query()
                ->where('status', 'published')
                ->with('states')
                ->find($selectedHolidayId);
        }

        return view('admin.overrides.create', [
            'holidays' => Holiday::query()
                ->where('status', 'published')
                ->orderByDesc('date')
                ->with('states')
                ->limit(100)
                ->get(),
            'selectedHoliday' => $selectedHoliday,
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $this->validateOverride($request);

        $holiday = isset($validated['holiday_id']) ? Holiday::find($validated['holiday_id']) : null;
        $date = Carbon::parse($validated['date']);

        $override = HolidayOverride::create([
            ...$validated,
            'state_code' => strtoupper($validated['state_code']),
            'date' => $date->toDateString(),
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $this->applyOverrideAction($validated, $holiday, $date);
        $auditLogger->logFromRequest(
            request: $request,
            action: 'override_created',
            entityType: 'holiday_override',
            entityId: $override->id,
            newValues: $auditLogger->modelSnapshot($override),
        );
        $auditLogger->logFromRequest(
            request: $request,
            action: 'override_approved',
            entityType: 'holiday_override',
            entityId: $override->id,
            newValues: ['approved_at' => $override->approved_at?->toDateTimeString()],
        );

        return redirect()
            ->route('admin.overrides.index')
            ->with('status', "Override #{$override->id} applied.");
    }

    public function edit(HolidayOverride $override): View
    {
        return view('admin.overrides.edit', [
            'override' => $override,
            'holidays' => Holiday::query()
                ->where('status', 'published')
                ->orWhere('id', $override->holiday_id)
                ->orderByDesc('date')
                ->with('states')
                ->limit(100)
                ->get(),
        ]);
    }

    public function update(Request $request, HolidayOverride $override, AuditLogger $auditLogger): RedirectResponse
    {
        $oldValues = $override->toArray();
        $validated = $this->validateOverride($request);
        $date = Carbon::parse($validated['date']);
        $holiday = isset($validated['holiday_id']) ? Holiday::find($validated['holiday_id']) : null;

        $override->update([
            ...$validated,
            'state_code' => strtoupper($validated['state_code']),
            'date' => $date->toDateString(),
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $this->applyOverrideAction($validated, $holiday, $date);
        $auditLogger->logFromRequest(
            request: $request,
            action: 'override_approved',
            entityType: 'holiday_override',
            entityId: $override->id,
            oldValues: $oldValues,
            newValues: $override->fresh()?->toArray(),
        );

        return redirect()
            ->route('admin.overrides.index')
            ->with('status', "Override #{$override->id} updated.");
    }

    public function destroy(Request $request, HolidayOverride $override, AuditLogger $auditLogger): RedirectResponse
    {
        $overrideId = $override->id;
        $oldValues = $override->toArray();
        $override->delete();
        $auditLogger->logFromRequest(
            request: $request,
            action: 'override_rejected',
            entityType: 'holiday_override',
            entityId: $overrideId,
            oldValues: $oldValues,
        );

        return redirect()
            ->route('admin.overrides.index')
            ->with('status', "Override #{$overrideId} deleted.");
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateOverride(Request $request): array
    {
        return $request->validate([
            'holiday_id' => ['nullable', 'exists:holidays,id'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'state_code' => ['required', 'string', 'max:10'],
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'action' => ['required', Rule::in(['add', 'remove', 'replace', 'rename', 'mark_subject_to_change'])],
            'reason' => ['required', 'string', 'max:5000'],
            'source_url' => ['nullable', 'url', 'max:2048'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function applyOverrideAction(array $validated, ?Holiday $holiday, Carbon $date): void
    {
        match ($validated['action']) {
            'add' => tap(Holiday::create([
                'year' => $validated['year'],
                'name' => $validated['name'],
                'date' => $date->toDateString(),
                'day_name' => $date->format('l'),
                'scope' => 'custom',
                'type' => 'custom',
                'status' => 'published',
                'source_note' => $validated['reason'],
            ]), function (Holiday $createdHoliday) use ($validated): void {
                $createdHoliday->syncStateCodes([strtoupper($validated['state_code'])]);
            }),
            'remove' => $holiday?->update(['status' => 'cancelled']),
            'replace', 'rename' => $holiday?->update([
                'name' => $validated['name'],
                'date' => $date->toDateString(),
                'day_name' => $date->format('l'),
                'status' => 'overridden',
            ]),
            'mark_subject_to_change' => $holiday?->update(['is_subject_to_change' => true]),
        };
    }
}
