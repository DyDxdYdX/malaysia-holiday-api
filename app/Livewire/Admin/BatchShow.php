<?php

namespace App\Livewire\Admin;

use App\Models\Holiday;
use App\Models\HolidayImportBatch;
use App\Support\AuditLogger;
use App\Support\MalaysiaStates;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class BatchShow extends Component
{
    public HolidayImportBatch $batch;

    public string $activeTab = 'approvals';

    public string $statusFilter = 'all';

    public string $search = '';

    /**
     * @var list<int|string>
     */
    public array $selectedHolidayIds = [];

    public bool $showApplyStatesModal = false;

    /**
     * @var list<string>
     */
    public array $bulkStateCodes = [];

    /**
     * @var list<int>
     */
    public array $stateTargetIds = [];

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

    public function updatedStatusFilter(): void
    {
        $this->pruneSelectionToVisibleHolidays();
    }

    public function updatedSearch(): void
    {
        $this->pruneSelectionToVisibleHolidays();
    }

    public function toggleSelectAll(): void
    {
        $visibleIds = $this->selectableHolidayIds();
        $selectedIds = $this->selectedIds();
        sort($visibleIds);
        sort($selectedIds);

        if ($visibleIds !== [] && $selectedIds === $visibleIds) {
            $this->selectedHolidayIds = [];

            return;
        }

        $this->selectedHolidayIds = $this->selectableHolidayIds();
    }

    public function toggleState(int $holidayId, string $stateCode): void
    {
        $holiday = $this->draftHoliday($holidayId);

        if ($holiday === null) {
            return;
        }

        $states = $holiday->stateCodes();

        if (in_array($stateCode, $states, true)) {
            $states = array_values(array_filter($states, fn (string $code): bool => $code !== $stateCode));
        } else {
            $states[] = $stateCode;
        }

        $this->persistHolidayStates($holiday, $states);
    }

    public function selectAllStates(int $holidayId): void
    {
        $holiday = $this->draftHoliday($holidayId);

        if ($holiday === null) {
            return;
        }

        $this->persistHolidayStates($holiday, MalaysiaStates::codes());
    }

    public function clearStates(int $holidayId): void
    {
        $holiday = $this->draftHoliday($holidayId);

        if ($holiday === null) {
            return;
        }

        $this->persistHolidayStates($holiday, []);
    }

    public function openApplyStatesModal(?int $holidayId = null): void
    {
        if ($this->batch->status === 'published') {
            return;
        }

        $this->resetErrorBag();

        $this->stateTargetIds = $holidayId !== null
            ? [$holidayId]
            : $this->selectedIds();

        $targets = $this->draftHolidaysForIds($this->stateTargetIds);

        if ($targets->isEmpty()) {
            $this->addError('selectedHolidayIds', __('Select at least one draft holiday first.'));

            return;
        }

        $this->stateTargetIds = $targets->pluck('id')->all();

        $uniqueStateSets = $targets
            ->map(fn (Holiday $holiday): string => implode(',', $holiday->stateCodes()))
            ->unique()
            ->values();

        $this->bulkStateCodes = $uniqueStateSets->count() === 1
            ? $targets->first()->stateCodes()
            : [];

        $this->showApplyStatesModal = true;
    }

    public function selectPresetInModal(string $preset): void
    {
        $codes = MalaysiaStates::presetCodes($preset);

        if ($codes === []) {
            return;
        }

        $this->bulkStateCodes = $codes;
    }

    public function applyStates(): void
    {
        if ($this->batch->status === 'published') {
            return;
        }

        $this->validate([
            'stateTargetIds' => ['required', 'array', 'min:1'],
            'stateTargetIds.*' => ['integer'],
            'bulkStateCodes' => ['required', 'array', 'min:1'],
            'bulkStateCodes.*' => ['string', Rule::in(MalaysiaStates::codes())],
        ]);

        $holidays = $this->draftHolidaysForIds($this->stateTargetIds);

        if ($holidays->isEmpty()) {
            $this->addError('bulkStateCodes', __('Select at least one draft holiday first.'));

            return;
        }

        foreach ($holidays as $holiday) {
            $holiday->syncStateCodes($this->bulkStateCodes);
        }

        $this->showApplyStatesModal = false;
        $this->bulkStateCodes = [];
        $this->stateTargetIds = [];
        $this->loadBatchRelations();

        session()->flash('status', __(':count holiday(s) updated with state selections.', ['count' => $holidays->count()]));
    }

    public function applyPresetToSelected(string $preset): void
    {
        if ($this->batch->status === 'published') {
            return;
        }

        $codes = MalaysiaStates::presetCodes($preset);

        if ($codes === []) {
            return;
        }

        $holidays = $this->draftHolidaysForIds($this->selectedIds());

        if ($holidays->isEmpty()) {
            $this->addError('selectedHolidayIds', __('Select at least one draft holiday first.'));

            return;
        }

        foreach ($holidays as $holiday) {
            $holiday->syncStateCodes($codes);
        }

        $this->loadBatchRelations();

        session()->flash('status', __(':count holiday(s) updated with the :preset preset.', [
            'count' => $holidays->count(),
            'preset' => MalaysiaStates::presets()[$preset]['label'],
        ]));
    }

    public function applyAllStatesToFederalDrafts(): void
    {
        if ($this->batch->status === 'published') {
            return;
        }

        $holidays = $this->batch->holidays
            ->where('status', 'draft')
            ->filter(fn (Holiday $holiday): bool => $holiday->scope === 'federal' && $holiday->stateCodes() === [])
            ->values();

        if ($holidays->isEmpty()) {
            session()->flash('error', __('No federal draft holidays need state selection.'));

            return;
        }

        foreach ($holidays as $holiday) {
            $holiday->syncStateCodes(MalaysiaStates::codes());
        }

        $this->loadBatchRelations();

        session()->flash('status', __(':count federal holiday(s) set to all states.', ['count' => $holidays->count()]));
    }

    public function clearStatesForSelected(): void
    {
        if ($this->batch->status === 'published') {
            return;
        }

        $holidays = $this->draftHolidaysForIds($this->selectedIds());

        if ($holidays->isEmpty()) {
            $this->addError('selectedHolidayIds', __('Select at least one draft holiday first.'));

            return;
        }

        foreach ($holidays as $holiday) {
            $holiday->syncStateCodes([]);
        }

        $this->loadBatchRelations();

        session()->flash('status', __('Cleared states for :count holiday(s).', ['count' => $holidays->count()]));
    }

    public function approveHoliday(int $holidayId, AuditLogger $auditLogger): void
    {
        $holiday = $this->holidayInBatch($holidayId);

        if ($holiday === null || ! in_array($holiday->status, ['draft', 'cancelled'], true)) {
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

        $this->removeFromSelection([$holiday->id]);
        $this->loadBatchRelations();
        session()->flash('status', __("Holiday ':name' approved.", ['name' => $holiday->name]));
    }

    public function rejectHoliday(int $holidayId, AuditLogger $auditLogger): void
    {
        $holiday = $this->holidayInBatch($holidayId);

        if ($holiday === null || ! in_array($holiday->status, ['draft', 'confirmed'], true)) {
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

        $this->removeFromSelection([$holiday->id]);
        $this->loadBatchRelations();
        session()->flash('status', __("Holiday ':name' rejected.", ['name' => $holiday->name]));
    }

    public function approveSelected(AuditLogger $auditLogger): void
    {
        $holidays = $this->draftHolidaysForIds($this->selectedIds());

        if ($holidays->isEmpty()) {
            $this->addError('selectedHolidayIds', __('Select at least one draft holiday first.'));

            return;
        }

        $approvedIds = [];
        $skippedCount = 0;

        foreach ($holidays as $holiday) {
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

            $approvedIds[] = $holiday->id;
        }

        $this->removeFromSelection($approvedIds);
        $this->flashApprovalResult(count($approvedIds), $skippedCount);
        $this->loadBatchRelations();
    }

    public function rejectSelected(AuditLogger $auditLogger): void
    {
        $holidays = $this->holidaysForIds($this->selectedIds())
            ->filter(fn (Holiday $holiday): bool => in_array($holiday->status, ['draft', 'confirmed'], true))
            ->values();

        if ($holidays->isEmpty()) {
            $this->addError('selectedHolidayIds', __('Select at least one holiday to reject.'));

            return;
        }

        foreach ($holidays as $holiday) {
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
        }

        $this->removeFromSelection($holidays->pluck('id')->all());
        $this->loadBatchRelations();
        session()->flash('status', __(':count holiday(s) rejected.', ['count' => $holidays->count()]));
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

        $this->selectedHolidayIds = [];
        $this->flashApprovalResult($approvedCount, $skippedCount);
        $this->loadBatchRelations();
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

        $this->selectedHolidayIds = [];
        $this->loadBatchRelations();
        session()->flash('status', __('Batch published.'));
    }

    public function render()
    {
        $isPdfExtractionPending = $this->batch->import_method === 'pdf_ai'
            && $this->batch->status === 'draft'
            && $this->batch->completed_at === null
            && $this->batch->failed_at === null;

        $filteredHolidays = $this->filteredHolidays();
        $needsReviewCount = $this->batch->holidays
            ->filter(fn (Holiday $holiday): bool => $holiday->status === 'draft' && $holiday->stateCodes() === [])
            ->count();
        $federalMissingCount = $this->batch->holidays
            ->filter(fn (Holiday $holiday): bool => $holiday->status === 'draft' && $holiday->scope === 'federal' && $holiday->stateCodes() === [])
            ->count();
        $selectableIds = $this->selectableHolidayIds($filteredHolidays);
        $selectedIds = $this->selectedIds();
        $selectedVisibleCount = count(array_intersect($selectedIds, $selectableIds));

        return view('livewire.admin.batch-show', [
            'isPdfExtractionPending' => $isPdfExtractionPending,
            'stateOptions' => MalaysiaStates::options(),
            'pdfColumns' => MalaysiaStates::pdfColumns(),
            'statePresets' => MalaysiaStates::presets(),
            'filteredHolidays' => $filteredHolidays,
            'needsReviewCount' => $needsReviewCount,
            'federalMissingCount' => $federalMissingCount,
            'selectableIds' => $selectableIds,
            'selectedIds' => $selectedIds,
            'allVisibleSelected' => $selectableIds !== [] && $selectedVisibleCount === count($selectableIds),
            'selectedCount' => count($selectedIds),
        ]);
    }

    /**
     * @return Collection<int, Holiday>
     */
    private function filteredHolidays(): Collection
    {
        $search = Str::lower(trim($this->search));

        return $this->batch->holidays
            ->filter(function (Holiday $holiday) use ($search): bool {
                if ($search !== '' && ! Str::contains(Str::lower($holiday->name), $search) && ! Str::contains($holiday->date->toDateString(), $search)) {
                    return false;
                }

                $needsReview = $holiday->status === 'draft' && $holiday->stateCodes() === [];

                return match ($this->statusFilter) {
                    'needs_review' => $needsReview,
                    'draft' => $holiday->status === 'draft',
                    'confirmed' => $holiday->status === 'confirmed',
                    'cancelled' => $holiday->status === 'cancelled',
                    default => true,
                };
            })
            ->values();
    }

    /**
     * @param  Collection<int, Holiday>|null  $holidays
     * @return list<int>
     */
    private function selectableHolidayIds(?Collection $holidays = null): array
    {
        return ($holidays ?? $this->filteredHolidays())
            ->reject(fn (Holiday $holiday): bool => $holiday->status === 'published')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    private function selectedIds(): array
    {
        return collect($this->selectedHolidayIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function pruneSelectionToVisibleHolidays(): void
    {
        $visibleIds = $this->selectableHolidayIds();

        $this->selectedHolidayIds = array_values(array_intersect($this->selectedIds(), $visibleIds));
    }

    /**
     * @param  list<int>  $ids
     */
    private function removeFromSelection(array $ids): void
    {
        $this->selectedHolidayIds = array_values(array_diff($this->selectedIds(), $ids));
    }

    private function draftHoliday(int $holidayId): ?Holiday
    {
        $holiday = $this->holidayInBatch($holidayId);

        if ($holiday === null || $holiday->status !== 'draft' || $this->batch->status === 'published') {
            return null;
        }

        return $holiday;
    }

    private function holidayInBatch(int $holidayId): ?Holiday
    {
        return $this->batch->holidays->firstWhere('id', $holidayId);
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, Holiday>
     */
    private function holidaysForIds(array $ids): Collection
    {
        return $this->batch->holidays
            ->whereIn('id', $ids)
            ->values();
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, Holiday>
     */
    private function draftHolidaysForIds(array $ids): Collection
    {
        return $this->holidaysForIds($ids)
            ->where('status', 'draft')
            ->values();
    }

    /**
     * @param  list<string>  $states
     */
    private function persistHolidayStates(Holiday $holiday, array $states): void
    {
        $holiday->syncStateCodes($states);
        $holiday->setRelation('states', $holiday->states()->get());
    }

    private function flashApprovalResult(int $approvedCount, int $skippedCount): void
    {
        if ($approvedCount > 0) {
            $message = __(':count holiday(s) approved.', ['count' => $approvedCount]);

            if ($skippedCount > 0) {
                $message .= ' '.__(':count holiday(s) skipped because they require state selection.', ['count' => $skippedCount]);
            }

            session()->flash('status', $message);

            return;
        }

        session()->flash('error', __('No holidays were approved. :count skipped due to missing state selections.', ['count' => $skippedCount]));
    }
}
