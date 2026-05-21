<?php

namespace App\Livewire\Admin;

use App\Models\Holiday;
use App\Models\HolidayImportBatch;
use App\Support\AuditLogger;
use App\Support\MalaysiaStates;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BatchShow extends Component
{
    public HolidayImportBatch $batch;

    public string $activeTab = 'approvals';

    public function mount(HolidayImportBatch $batch): void
    {
        $this->batch = $batch;
        $this->loadBatchRelations();
    }

    public function loadBatchRelations(): void
    {
        $this->batch->load([
            'source',
            'holidays' => fn ($query) => $query->orderBy('date')->with('states'),
            'importRows' => fn ($query) => $query->orderBy('row_number'),
        ]);
    }

    public function toggleState(int $holidayId, string $stateCode): void
    {
        $holiday = Holiday::findOrFail($holidayId);

        if ($holiday->status !== 'draft') {
            return;
        }

        $states = $holiday->stateCodes();

        if (in_array($stateCode, $states, true)) {
            $states = array_filter($states, fn ($code): bool => $code !== $stateCode);
        } else {
            $states[] = $stateCode;
        }

        $holiday->syncStateCodes($states);
        $this->loadBatchRelations();
    }

    public function selectAllStates(int $holidayId): void
    {
        $holiday = Holiday::findOrFail($holidayId);

        if ($holiday->status !== 'draft') {
            return;
        }

        $holiday->syncStateCodes(MalaysiaStates::codes());
        $this->loadBatchRelations();
    }

    public function clearStates(int $holidayId): void
    {
        $holiday = Holiday::findOrFail($holidayId);

        if ($holiday->status !== 'draft') {
            return;
        }

        $holiday->syncStateCodes([]);
        $this->loadBatchRelations();
    }

    public function approveHoliday(int $holidayId, AuditLogger $auditLogger): void
    {
        $holiday = Holiday::findOrFail($holidayId);

        if (! in_array($holiday->status, ['draft', 'cancelled'], true)) {
            return;
        }

        if ($holiday->stateCodes() === []) {
            $this->addError("holiday-{$holidayId}", __('Select at least one state before approving this holiday.'));

            return;
        }

        $oldValues = $holiday->toArray();
        $holiday->update(['status' => 'confirmed']);

        $auditLogger->logFromRequest(
            request: request(),
            action: 'holiday_updated',
            entityType: 'holiday',
            entityId: $holiday->id,
            oldValues: $oldValues,
            newValues: $holiday->fresh()?->toArray(),
        );

        $this->loadBatchRelations();
        session()->flash('status', __("Holiday ':name' approved.", ['name' => $holiday->name]));
    }

    public function rejectHoliday(int $holidayId, AuditLogger $auditLogger): void
    {
        $holiday = Holiday::findOrFail($holidayId);

        if (! in_array($holiday->status, ['draft', 'confirmed'], true)) {
            return;
        }

        $oldValues = $holiday->toArray();
        $holiday->update(['status' => 'cancelled']);

        $auditLogger->logFromRequest(
            request: request(),
            action: 'holiday_deleted',
            entityType: 'holiday',
            entityId: $holiday->id,
            oldValues: $oldValues,
            newValues: $holiday->fresh()?->toArray(),
        );

        $this->loadBatchRelations();
        session()->flash('status', __("Holiday ':name' rejected.", ['name' => $holiday->name]));
    }

    public function approveAll(AuditLogger $auditLogger): void
    {
        $draftHolidays = $this->batch->holidays()->where('status', 'draft')->get();

        if ($draftHolidays->isEmpty()) {
            session()->flash('error', __('No draft holidays to approve.'));

            return;
        }

        $approvedCount = 0;
        $skippedCount = 0;

        foreach ($draftHolidays as $holiday) {
            if ($holiday->stateCodes() === []) {
                $skippedCount++;

                continue;
            }

            $oldValues = $holiday->toArray();
            $holiday->update(['status' => 'confirmed']);

            $auditLogger->logFromRequest(
                request: request(),
                action: 'holiday_updated',
                entityType: 'holiday',
                entityId: $holiday->id,
                oldValues: $oldValues,
                newValues: $holiday->fresh()?->toArray(),
            );

            $approvedCount++;
        }

        $this->loadBatchRelations();

        if ($approvedCount > 0) {
            $msg = __(':count holiday(s) approved.', ['count' => $approvedCount]);
            if ($skippedCount > 0) {
                $msg .= ' '.__(':count holiday(s) skipped because they require state selection.', ['count' => $skippedCount]);
            }
            session()->flash('status', $msg);
        } else {
            session()->flash('error', __('No holidays were approved. :count skipped due to missing state selections.', ['count' => $skippedCount]));
        }
    }

    public function publish(AuditLogger $auditLogger): void
    {
        if ($this->batch->invalid_rows > 0) {
            session()->flash('error', __('Batch still has unresolved invalid rows.'));

            return;
        }

        $hasHolidaysMissingStates = $this->batch->holidays()
            ->where('status', '!=', 'cancelled')
            ->whereDoesntHave('states')
            ->exists();

        if ($hasHolidaysMissingStates) {
            session()->flash('error', __('Batch still has holidays without state selections.'));

            return;
        }

        $oldBatchValues = $this->batch->toArray();
        $this->batch->holidays()
            ->whereIn('status', ['draft', 'confirmed'])
            ->update(['status' => 'published']);

        $this->batch->update([
            'status' => 'published',
            'published_by' => Auth::id(),
            'published_at' => now(),
        ]);

        $this->batch->source()->update(['status' => 'active']);

        $auditLogger->logFromRequest(
            request: request(),
            action: 'holiday_published',
            entityType: 'holiday_import_batch',
            entityId: $this->batch->id,
            oldValues: $oldBatchValues,
            newValues: $this->batch->fresh()?->toArray(),
        );

        $auditLogger->logFromRequest(
            request: request(),
            action: 'source_updated',
            entityType: 'holiday_source',
            entityId: $this->batch->holiday_source_id,
            newValues: ['status' => 'active'],
        );

        $this->loadBatchRelations();
        session()->flash('status', __('Batch published.'));
    }

    public function render()
    {
        $isPdfExtractionPending = $this->batch->import_method === 'pdf_ai'
            && $this->batch->status === 'draft'
            && $this->batch->completed_at === null
            && $this->batch->failed_at === null;

        return view('livewire.admin.batch-show', [
            'isPdfExtractionPending' => $isPdfExtractionPending,
            'stateOptions' => MalaysiaStates::options(),
        ]);
    }
}
