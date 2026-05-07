<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HolidayController extends Controller
{
    public function edit(Holiday $holiday): View
    {
        return view('admin.holidays.edit', [
            'holiday' => $holiday,
        ]);
    }

    public function update(Request $request, Holiday $holiday): RedirectResponse
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

        $holiday->update([
            ...$validated,
            'state_code' => strtoupper($validated['state_code']),
            'date' => $date->toDateString(),
            'day_name' => $date->format('l'),
            'is_subject_to_change' => $request->boolean('is_subject_to_change'),
            'status' => 'confirmed',
        ]);

        return redirect()
            ->route('admin.batches.show', $holiday->holiday_import_batch_id)
            ->with('status', 'Holiday updated.');
    }

    public function reject(Holiday $holiday): RedirectResponse
    {
        $holiday->update(['status' => 'cancelled']);

        return redirect()
            ->route('admin.batches.show', $holiday->holiday_import_batch_id)
            ->with('status', 'Holiday rejected.');
    }
}
