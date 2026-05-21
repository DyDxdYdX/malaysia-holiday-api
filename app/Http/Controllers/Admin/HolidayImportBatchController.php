<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use App\Models\HolidayImportBatch;
use App\Support\AuditLogger;
use App\Support\MalaysiaStates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HolidayImportBatchController extends Controller
{
    public function index(): View
    {
        return view('admin.batches.index', [
            'batches' => HolidayImportBatch::query()
                ->with(['source', 'importer'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function show(HolidayImportBatch $batch): Response
    {
        $batch->load([
            'source',
            'holidays' => fn ($query) => $query->orderBy('date')->with('states'),
            'importRows' => fn ($query) => $query->orderBy('row_number'),
        ]);

        $isPdfExtractionPending = $batch->import_method === 'pdf_ai'
            && $batch->status === 'draft'
            && $batch->completed_at === null
            && $batch->failed_at === null;

        $response = response()->view('admin.batches.show', [
            'batch' => $batch,
            'isPdfExtractionPending' => $isPdfExtractionPending,
            'stateOptions' => MalaysiaStates::options(),
        ]);

        if ($isPdfExtractionPending) {
            $response->headers->set('Refresh', '5');
        }

        return $response;
    }

    public function publish(Request $request, HolidayImportBatch $batch, AuditLogger $auditLogger): RedirectResponse
    {
        abort_if($batch->invalid_rows > 0, 422, 'Batch still has unresolved invalid rows.');
        abort_if(
            $batch->holidays()
                ->where('status', '!=', 'cancelled')
                ->whereDoesntHave('states')
                ->exists(),
            422,
            'Batch still has holidays without state selections.'
        );

        $oldBatchValues = $batch->toArray();
        $batch->holidays()
            ->whereIn('status', ['draft', 'confirmed'])
            ->update(['status' => 'published']);

        $batch->update([
            'status' => 'published',
            'published_by' => $request->user()->id,
            'published_at' => now(),
        ]);

        $batch->source()->update(['status' => 'active']);
        $auditLogger->logFromRequest(
            request: $request,
            action: 'holiday_published',
            entityType: 'holiday_import_batch',
            entityId: $batch->id,
            oldValues: $oldBatchValues,
            newValues: $batch->fresh()?->toArray(),
        );
        $auditLogger->logFromRequest(
            request: $request,
            action: 'source_updated',
            entityType: 'holiday_source',
            entityId: $batch->holiday_source_id,
            newValues: ['status' => 'active'],
        );

        return redirect()
            ->route('admin.batches.show', $batch)
            ->with('status', 'Batch published.');
    }

    public function approveSelected(Request $request, HolidayImportBatch $batch, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'holiday_ids' => ['required', 'array'],
            'holiday_ids.*' => ['integer'],
            'state_codes' => ['nullable', 'array'],
            'state_codes.*' => ['array', 'min:1'],
            'state_codes.*.*' => ['string', 'distinct', Rule::in(MalaysiaStates::codes())],
        ]);

        $selectedHolidayIds = array_map('intval', $validated['holiday_ids']);
        $stateSelections = $this->stateSelections($request->input('state_codes', []));

        $holidays = Holiday::query()
            ->where('holiday_import_batch_id', $batch->id)
            ->where('status', 'draft')
            ->whereIn('id', $selectedHolidayIds)
            ->get();

        if ($holidays->isEmpty()) {
            return redirect()
                ->route('admin.batches.show', $batch)
                ->withErrors(['holiday_ids' => 'No draft holidays were selected for approval.']);
        }

        foreach ($holidays as $holiday) {
            $stateCodes = $stateSelections[$holiday->id] ?? [];

            if ($stateCodes === []) {
                return redirect()
                    ->route('admin.batches.show', $batch)
                    ->withErrors(["state_codes.{$holiday->id}" => 'Select at least one state before approving this holiday.']);
            }

            $oldValues = $holiday->toArray();
            $holiday->syncStateCodes($stateCodes);
            $holiday->update(['status' => 'confirmed']);

            $auditLogger->logFromRequest(
                request: $request,
                action: 'holiday_updated',
                entityType: 'holiday',
                entityId: $holiday->id,
                oldValues: $oldValues,
                newValues: $holiday->fresh()?->toArray(),
            );
        }

        return redirect()
            ->route('admin.batches.show', $batch)
            ->with('status', "{$holidays->count()} holiday(s) approved.");
    }

    /**
     * @return array<int, list<string>>
     */
    private function stateSelections(mixed $stateSelections): array
    {
        if (! is_array($stateSelections)) {
            return [];
        }

        $normalized = [];

        foreach ($stateSelections as $holidayId => $stateCodes) {
            if (! is_array($stateCodes)) {
                continue;
            }

            $normalized[(int) $holidayId] = collect($stateCodes)
                ->map(fn (mixed $stateCode): string => strtoupper(trim((string) $stateCode)))
                ->filter(fn (string $stateCode): bool => in_array($stateCode, MalaysiaStates::codes(), true))
                ->unique()
                ->values()
                ->all();
        }

        return $normalized;
    }
}
