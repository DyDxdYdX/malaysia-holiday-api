<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\HolidayOverride;
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
        return view('admin.overrides.create', [
            'holidays' => Holiday::query()
                ->where('status', 'published')
                ->orderByDesc('date')
                ->limit(100)
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'holiday_id' => ['nullable', 'exists:holidays,id'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'state_code' => ['required', 'string', 'max:10'],
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'action' => ['required', Rule::in(['add', 'remove', 'replace', 'rename', 'mark_subject_to_change'])],
            'reason' => ['required', 'string', 'max:5000'],
            'source_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $holiday = isset($validated['holiday_id']) ? Holiday::find($validated['holiday_id']) : null;
        $date = Carbon::parse($validated['date']);

        $override = HolidayOverride::create([
            ...$validated,
            'state_code' => strtoupper($validated['state_code']),
            'date' => $date->toDateString(),
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        match ($validated['action']) {
            'add' => Holiday::create([
                'year' => $validated['year'],
                'state_code' => strtoupper($validated['state_code']),
                'name' => $validated['name'],
                'date' => $date->toDateString(),
                'day_name' => $date->format('l'),
                'scope' => 'custom',
                'type' => 'custom',
                'status' => 'published',
                'source_note' => $validated['reason'],
            ]),
            'remove' => $holiday?->update(['status' => 'cancelled']),
            'replace', 'rename' => $holiday?->update([
                'name' => $validated['name'],
                'date' => $date->toDateString(),
                'day_name' => $date->format('l'),
                'status' => 'overridden',
            ]),
            'mark_subject_to_change' => $holiday?->update(['is_subject_to_change' => true]),
        };

        return redirect()
            ->route('admin.overrides.index')
            ->with('status', "Override #{$override->id} applied.");
    }
}
