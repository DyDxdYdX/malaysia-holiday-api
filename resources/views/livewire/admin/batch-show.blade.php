<div class="admin-page">
    <div class="admin-header">
        <div>
            <p class="app-label text-brand-red">{{ __('Batch Review') }}</p>
            <h1 class="app-page-title mt-2">{{ __('Batch #:id', ['id' => $batch->id]) }}</h1>
            <p class="app-page-copy mt-2">
                <span class="capitalize">{{ $batch->status }}</span> ·
                <span class="uppercase">{{ $batch->import_method }}</span> ·
                {{ $batch->valid_rows }}/{{ $batch->total_rows }} {{ __('valid rows') }}
            </p>
            @if ($batch->provider || $batch->model)
                <p class="app-page-copy mt-1">{{ $batch->provider }} · {{ $batch->model }}</p>
            @endif
        </div>

        @php
            $hasHolidaysMissingStates = $batch->holidays
                ->where('status', '!=', 'cancelled')
                ->contains(function ($holiday): bool {
                    return empty($holiday->stateCodes());
                });
            $hasDraftHolidays = $batch->holidays->where('status', 'draft')->isNotEmpty();
            $isPublishDisabled = $batch->status === 'published'
                || $batch->invalid_rows > 0
                || $hasHolidaysMissingStates
                || $hasDraftHolidays;
        @endphp

        <div class="flex items-center gap-3">
            <flux:button
                :href="route('admin.batches.export-pdf', $batch)"
                target="_blank"
                icon="arrow-down-tray"
                variant="filled"
                class="cursor-pointer"
            >
                {{ __('Export PDF') }}
            </flux:button>
            @if ($batch->status !== 'published')
                <flux:button
                    wire:click="publish"
                    variant="primary"
                    icon="check-circle"
                    :disabled="$isPublishDisabled"
                    class="cursor-pointer"
                >
                    {{ __('Publish') }}
                </flux:button>
            @else
                <span class="app-badge app-badge-navy px-3 py-1.5 text-sm font-semibold">{{ __('Published') }}</span>
            @endif
        </div>
    </div>

    @if ($batch->failed_at)
        <div class="app-section border-brand-red/30">
            <p class="app-label text-brand-red">{{ __('Extraction failed') }}</p>
            <p class="mt-2 text-app-copy">{{ $batch->failure_reason }}</p>
        </div>
    @endif

    @if ($isPdfExtractionPending)
        <div wire:poll.5s="loadBatchRelations" class="app-section flex items-center gap-4 border-brand-gold/40">
            <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-gold/10 text-brand-gold">
                <flux:icon.loading class="size-5" />
            </div>
            <div>
                <p class="font-semibold text-brand-navy dark:text-white">{{ __('PDF extraction in progress') }}</p>
                <p class="app-page-copy mt-1">{{ __('Gemini is extracting holiday rows from the source PDF. This page will refresh automatically.') }}</p>
            </div>
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-4">
        <div class="admin-stat-card"><p class="app-label">{{ __('Total') }}</p><p class="mt-4 text-3xl font-bold text-brand-navy dark:text-white">{{ $batch->total_rows }}</p></div>
        <div class="admin-stat-card"><p class="app-label">{{ __('Valid') }}</p><p class="mt-4 text-3xl font-bold text-brand-navy dark:text-white">{{ $batch->valid_rows }}</p></div>
        <div class="admin-stat-card"><p class="app-label">{{ __('Invalid') }}</p><p class="mt-4 text-3xl font-bold text-brand-red">{{ $batch->invalid_rows }}</p></div>
        <div class="admin-stat-card"><p class="app-label">{{ __('Warnings') }}</p><p class="mt-4 text-3xl font-bold text-brand-gold">{{ $batch->warning_rows }}</p></div>
    </div>

    <div class="space-y-6">
        @if (session()->has('status'))
            <div class="flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800/30 dark:bg-green-950/30 dark:text-green-400">
                <flux:icon.check-circle class="size-5 shrink-0" />
                <div>{{ session('status') }}</div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-brand-red dark:border-red-800/30 dark:bg-red-950/30 dark:text-red-400">
                <flux:icon.exclamation-circle class="size-5 shrink-0" />
                <div>{{ session('error') }}</div>
            </div>
        @endif

        <div class="flex border-b border-app-outline">
            <button
                type="button"
                wire:click="$set('activeTab', 'approvals')"
                class="{{ $activeTab === 'approvals' ? 'border-brand-red text-brand-red font-semibold' : 'border-transparent text-app-copy-muted hover:text-app-copy' }} -mb-px cursor-pointer border-b-2 px-4 py-2.5 text-sm transition-all focus:outline-hidden"
            >
                {{ __('Holidays for Review') }}
                <span class="ml-1.5 rounded-full bg-slate-100 px-1.5 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                    {{ $batch->holidays->whereIn('status', ['draft', 'confirmed'])->count() }}
                </span>
            </button>
            <button
                type="button"
                wire:click="$set('activeTab', 'audit')"
                class="{{ $activeTab === 'audit' ? 'border-brand-red text-brand-red font-semibold' : 'border-transparent text-app-copy-muted hover:text-app-copy' }} -mb-px cursor-pointer border-b-2 px-4 py-2.5 text-sm transition-all focus:outline-hidden"
            >
                {{ __('Row Audit Logs & Validation') }}
                <span class="ml-1.5 rounded-full bg-slate-100 px-1.5 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                    {{ $batch->importRows->count() }}
                </span>
            </button>
        </div>

        @if ($activeTab === 'approvals')
            @if ($needsReviewCount > 0 && $batch->status !== 'published')
                <div class="app-section border-brand-gold/40">
                    <p class="app-label text-brand-gold">{{ __('Needs attention') }}</p>
                    <p class="mt-2 font-semibold text-brand-navy dark:text-white">{{ __('State applicability requires manual review.') }}</p>
                    <p class="app-page-copy mt-1">
                        {{ __(':count holiday(s) still need state assignment. Tick the same state columns as the JPM PDF, or apply a preset to selected rows.', ['count' => $needsReviewCount]) }}
                    </p>
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <flux:button
                            size="sm"
                            variant="filled"
                            wire:click="$set('statusFilter', 'needs_review')"
                            class="cursor-pointer"
                        >
                            {{ __('Show holidays needing states') }}
                        </flux:button>
                        @if ($federalMissingCount > 0)
                            <flux:button
                                size="sm"
                                variant="primary"
                                icon="map"
                                wire:click="applyAllStatesToFederalDrafts"
                                class="cursor-pointer"
                            >
                                {{ __('Apply all states to :count federal drafts', ['count' => $federalMissingCount]) }}
                            </flux:button>
                        @endif
                    </div>
                </div>
            @endif

            <div class="app-card overflow-hidden">
                <div class="flex flex-col gap-4 border-b border-app-border px-4 py-3">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <p class="app-label">{{ __('Holiday approvals') }}</p>
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($batch->status !== 'published' && ! $isPdfExtractionPending)
                                <flux:button
                                    wire:click="openAddHolidayModal"
                                    variant="filled"
                                    icon="plus"
                                    class="cursor-pointer"
                                >
                                    {{ __('Add missing holiday') }}
                                </flux:button>
                            @endif
                            @if ($hasDraftHolidays && $batch->status !== 'published')
                                <flux:button
                                    wire:click="approveAll"
                                    variant="primary"
                                    icon="check"
                                    class="cursor-pointer"
                                >
                                    {{ __('Approve All Drafts') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                        <div class="flex flex-wrap items-center gap-1.5">
                            @foreach ([
                                'all' => __('All'),
                                'needs_review' => __('Needs states'),
                                'draft' => __('Draft'),
                                'confirmed' => __('Confirmed'),
                                'cancelled' => __('Rejected'),
                            ] as $filter => $label)
                                <button
                                    type="button"
                                    wire:click="$set('statusFilter', '{{ $filter }}')"
                                    class="{{ $statusFilter === $filter ? 'bg-brand-navy text-white dark:bg-white dark:text-brand-navy' : 'bg-app-surface-low text-app-copy-muted hover:text-app-copy' }} cursor-pointer rounded-full px-3 py-1 text-xs font-semibold transition-colors"
                                >
                                    {{ $label }}
                                    <span class="ml-1 opacity-70">
                                        @if ($filter === 'all')
                                            {{ $batch->holidays->count() }}
                                        @elseif ($filter === 'needs_review')
                                            {{ $needsReviewCount }}
                                        @else
                                            {{ $batch->holidays->where('status', $filter === 'cancelled' ? 'cancelled' : $filter)->count() }}
                                        @endif
                                    </span>
                                </button>
                            @endforeach
                        </div>

                        <div class="w-full xl:max-w-xs">
                            <flux:input
                                wire:model.live.debounce.300ms="search"
                                icon="magnifying-glass"
                                :placeholder="__('Search name or date')"
                                size="sm"
                            />
                        </div>
                    </div>

                    @if ($batch->status !== 'published')
                        <div class="flex flex-wrap items-center gap-2 rounded-lg border border-app-outline bg-app-surface-low/60 px-3 py-2.5">
                            <p class="mr-1 text-sm font-semibold text-brand-navy dark:text-white">
                                {{ __(':count selected', ['count' => $selectedCount]) }}
                            </p>

                            <flux:button
                                size="sm"
                                variant="primary"
                                icon="map"
                                wire:click="openApplyStatesModal"
                                :disabled="$selectedCount === 0"
                                class="cursor-pointer"
                            >
                                {{ __('Set states') }}
                            </flux:button>
                            <flux:button
                                size="sm"
                                variant="filled"
                                wire:click="applyPresetToSelected('all')"
                                :disabled="$selectedCount === 0"
                                class="cursor-pointer"
                            >
                                {{ __('All states') }}
                            </flux:button>
                            <flux:button
                                size="sm"
                                variant="filled"
                                wire:click="applyPresetToSelected('peninsular')"
                                :disabled="$selectedCount === 0"
                                class="cursor-pointer"
                            >
                                {{ __('Peninsular') }}
                            </flux:button>
                            <flux:button
                                size="sm"
                                variant="filled"
                                wire:click="applyPresetToSelected('east_malaysia')"
                                :disabled="$selectedCount === 0"
                                class="cursor-pointer"
                            >
                                {{ __('East Malaysia') }}
                            </flux:button>
                            <flux:button
                                size="sm"
                                variant="primary"
                                icon="check"
                                wire:click="approveSelected"
                                :disabled="$selectedCount === 0"
                                class="cursor-pointer"
                            >
                                {{ __('Approve selected') }}
                            </flux:button>
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="x-mark"
                                wire:click="rejectSelected"
                                :disabled="$selectedCount === 0"
                                class="cursor-pointer text-brand-red"
                            >
                                {{ __('Reject selected') }}
                            </flux:button>
                            <flux:button
                                size="sm"
                                variant="ghost"
                                wire:click="clearStatesForSelected"
                                :disabled="$selectedCount === 0"
                                class="cursor-pointer"
                            >
                                {{ __('Clear states') }}
                            </flux:button>
                            @if ($selectedCount > 0)
                                <button
                                    type="button"
                                    wire:click="$set('selectedHolidayIds', [])"
                                    class="cursor-pointer text-xs font-semibold text-app-copy-muted hover:text-app-copy"
                                >
                                    {{ __('Clear selection') }}
                                </button>
                            @endif
                        </div>
                    @endif

                    @error('selectedHolidayIds')
                        <p class="text-sm text-brand-red">{{ $message }}</p>
                    @enderror
                </div>

                <div class="overflow-x-auto">
                    <table class="app-table app-table-compact state-grid-table">
                        <thead>
                            <tr>
                                @if ($batch->status !== 'published')
                                    <th class="state-grid-sticky left-0 w-10">
                                        <flux:checkbox
                                            :checked="$allVisibleSelected"
                                            wire:click="toggleSelectAll"
                                            :disabled="$selectableIds === []"
                                            class="cursor-pointer"
                                        />
                                    </th>
                                @endif
                                <th class="state-grid-sticky {{ $batch->status !== 'published' ? 'left-10' : 'left-0' }} min-w-56">
                                    <span>{{ __('Holiday') }}</span>
                                    <p class="mt-1 text-[9px] font-medium tracking-normal text-app-copy-muted normal-case">{{ __('Columns match the JPM PDF, left to right.') }}</p>
                                </th>
                                @foreach ($pdfColumns as $code => $label)
                                    <th class="state-grid-col" title="{{ $code }} · {{ $stateOptions[$code] }}">
                                        <span class="state-grid-heading">{{ $label }}</span>
                                        <span class="mt-1 block text-[9px] font-bold tracking-wider text-brand-navy dark:text-slate-300">{{ $code }}</span>
                                    </th>
                                @endforeach
                                <th>{{ __('Status') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($filteredHolidays as $holiday)
                                @php
                                    $stateCodes = $holiday->stateCodes();
                                    $isSelectable = $holiday->status !== 'published' && $batch->status !== 'published';
                                    $canEditStates = $holiday->status === 'draft' && $batch->status !== 'published';
                                    $marker = \App\Support\MalayCalendar::marker($holiday->scope);
                                @endphp
                                <tr wire:key="holiday-row-{{ $holiday->id }}" class="{{ in_array($holiday->id, $selectedIds, true) ? 'bg-brand-red/5' : '' }}">
                                    @if ($batch->status !== 'published')
                                        <td class="state-grid-sticky left-0 w-10">
                                            @if ($isSelectable)
                                                <flux:checkbox
                                                    wire:model.live="selectedHolidayIds"
                                                    value="{{ $holiday->id }}"
                                                    class="cursor-pointer"
                                                />
                                            @endif
                                        </td>
                                    @endif
                                    <td class="state-grid-sticky {{ $batch->status !== 'published' ? 'left-10' : 'left-0' }} min-w-56">
                                        <p class="font-medium text-brand-navy dark:text-white">
                                            {{ $holiday->name }}@if ($holiday->is_subject_to_change)*@endif
                                        </p>
                                        <p class="mt-0.5 text-sm text-brand-navy dark:text-slate-100">
                                            <span class="font-semibold">{{ \App\Support\MalayCalendar::dateLabel($holiday->date) }}</span>
                                            <span class="text-app-copy-muted"> · {{ \App\Support\MalayCalendar::dayName($holiday->date) }}</span>
                                        </p>
                                        <div class="mt-1 flex flex-wrap items-center gap-1">
                                            <span class="app-badge {{ $marker === 'P' ? 'app-badge-red' : 'app-badge-navy' }}">({{ $marker }})</span>
                                            <span class="app-badge app-badge-navy">{{ $holiday->scope }}</span>
                                            @if ($holiday->is_subject_to_change)
                                                <span class="app-badge app-badge-gold">{{ __('Subject to change') }}</span>
                                            @endif
                                            @if ($stateCodes === [])
                                                <span class="app-badge app-badge-gold">{{ __('Needs review') }}</span>
                                            @endif
                                        </div>
                                        @error("holiday-{$holiday->id}")
                                            <p class="mt-1 text-sm text-brand-red">{{ $message }}</p>
                                        @enderror
                                    </td>
                                    @foreach ($pdfColumns as $code => $label)
                                        @php
                                            $isOn = in_array($code, $stateCodes, true);
                                        @endphp
                                        <td class="state-grid-col" wire:key="holiday-{{ $holiday->id }}-state-{{ $code }}">
                                            @if ($canEditStates)
                                                <button
                                                    type="button"
                                                    wire:click="toggleState({{ $holiday->id }}, '{{ $code }}')"
                                                    wire:loading.class="opacity-50"
                                                    wire:target="toggleState({{ $holiday->id }}, '{{ $code }}')"
                                                    class="state-grid-cell state-grid-cell-button {{ $isOn ? 'state-grid-cell-on state-grid-cell-'.$marker : '' }}"
                                                    title="{{ $isOn ? __('Remove :state', ['state' => $label]) : __('Add :state', ['state' => $label]) }}"
                                                    aria-pressed="{{ $isOn ? 'true' : 'false' }}"
                                                    aria-label="{{ __(':state for :name', ['state' => $label, 'name' => $holiday->name]) }}"
                                                >
                                                    {{ $isOn ? '✓' : '' }}
                                                </button>
                                            @else
                                                <div class="state-grid-cell {{ $isOn ? 'state-grid-cell-on state-grid-cell-'.$marker : '' }}" title="{{ $label }}">
                                                    {{ $isOn ? '✓' : '' }}
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td>
                                        <span class="app-badge {{ $holiday->status === 'published' ? 'app-badge-gold' : ($holiday->status === 'confirmed' ? 'app-badge-navy' : ($holiday->status === 'cancelled' ? '' : 'app-badge-red')) }}">
                                            {{ $holiday->status }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flex items-center justify-end gap-1.5">
                                            @if ($batch->status !== 'published')
                                                @if ($holiday->status === 'draft')
                                                    <flux:button
                                                        size="xs"
                                                        variant="primary"
                                                        icon="check"
                                                        wire:click="approveHoliday({{ $holiday->id }})"
                                                        class="cursor-pointer"
                                                    >
                                                        {{ __('Approve') }}
                                                    </flux:button>
                                                    <flux:button
                                                        size="xs"
                                                        variant="ghost"
                                                        wire:click="rejectHoliday({{ $holiday->id }})"
                                                        class="cursor-pointer text-brand-red"
                                                    >
                                                        {{ __('Reject') }}
                                                    </flux:button>
                                                @elseif ($holiday->status === 'confirmed')
                                                    <flux:button
                                                        size="xs"
                                                        variant="ghost"
                                                        wire:click="rejectHoliday({{ $holiday->id }})"
                                                        class="cursor-pointer text-brand-red"
                                                    >
                                                        {{ __('Reject') }}
                                                    </flux:button>
                                                @elseif ($holiday->status === 'cancelled')
                                                    <flux:button
                                                        size="xs"
                                                        variant="primary"
                                                        icon="check"
                                                        wire:click="approveHoliday({{ $holiday->id }})"
                                                        class="cursor-pointer"
                                                    >
                                                        {{ __('Approve') }}
                                                    </flux:button>
                                                @endif
                                            @endif
                                            <a class="admin-action-link text-xs" href="{{ route('admin.holidays.edit', $holiday) }}" wire:navigate>{{ __('Edit') }}</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ ($batch->status !== 'published' ? 4 : 3) + count($pdfColumns) }}" class="py-8 text-center text-app-copy-muted">
                                        {{ __('No holidays match this filter.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($activeTab === 'audit')
            <div class="app-card overflow-hidden">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Row') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('State') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Notes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($batch->importRows as $row)
                            @php
                                $payload = $row->normalized_payload ?? [];
                            @endphp
                            <tr wire:key="audit-row-{{ $row->id }}">
                                <td class="font-mono">{{ $row->row_number }}</td>
                                <td><span class="app-badge {{ $row->status === 'invalid' ? 'app-badge-red' : ($row->status === 'warning' ? 'app-badge-gold' : 'app-badge-navy') }}">{{ $row->status }}</span></td>
                                <td class="font-mono">{{ $payload['date'] ?? '—' }}</td>
                                <td>{{ $payload['state_codes'] ?? '—' }}</td>
                                <td>{{ $payload['name'] ?? '—' }}</td>
                                <td>
                                    @foreach (($row->errors ?? []) as $error)
                                        <p class="text-brand-red">{{ $error }}</p>
                                    @endforeach
                                    @foreach (($row->warnings ?? []) as $warning)
                                        <p class="text-brand-gold">{{ $warning }}</p>
                                    @endforeach
                                    @if ($row->confidence !== null)
                                        <p class="font-mono text-xs text-app-muted">{{ __('Confidence') }}: {{ $row->confidence }}</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">{{ __('No row audit entries yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <flux:modal wire:model="showApplyStatesModal" class="max-w-2xl">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Set states') }}</flux:heading>
                <flux:subheading>
                    {{ __('Apply the same state selection to :count selected holiday(s).', ['count' => count($stateTargetIds)]) }}
                </flux:subheading>
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach ($statePresets as $presetKey => $preset)
                    <flux:button
                        size="sm"
                        variant="filled"
                        wire:click="selectPresetInModal('{{ $presetKey }}')"
                        class="cursor-pointer"
                    >
                        {{ __($preset['label']) }}
                    </flux:button>
                @endforeach
                <flux:button
                    size="sm"
                    variant="ghost"
                    wire:click="$set('bulkStateCodes', [])"
                    class="cursor-pointer"
                >
                    {{ __('Clear') }}
                </flux:button>
            </div>

            <div class="overflow-x-auto rounded-lg border border-app-outline p-3">
                <p class="mb-3 text-xs font-semibold tracking-wide text-app-copy-muted uppercase">{{ __('Same column order as the JPM PDF') }}</p>
                <div class="flex min-w-max gap-1">
                    @foreach ($pdfColumns as $code => $label)
                        <label class="flex w-8 cursor-pointer flex-col items-center gap-1.5" title="{{ $code }} · {{ $stateOptions[$code] }}">
                            <span class="state-grid-heading">{{ $label }}</span>
                            <flux:checkbox
                                wire:model="bulkStateCodes"
                                value="{{ $code }}"
                                class="cursor-pointer"
                            />
                            <span class="text-[9px] font-bold tracking-wider text-brand-navy dark:text-slate-300">{{ $code }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            @error('bulkStateCodes')
                <p class="text-sm text-brand-red">{{ $message }}</p>
            @enderror

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled" class="cursor-pointer">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" icon="check" wire:click="applyStates" class="cursor-pointer">
                    {{ __('Apply states') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showAddHolidayModal" class="max-w-3xl">
        <form wire:submit="addMissingHoliday" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Add missing holiday') }}</flux:heading>
                <flux:subheading>
                    {{ __('Add a holiday the PDF extraction missed. It will join this batch as a draft for :year.', ['year' => $batch->year]) }}
                </flux:subheading>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <flux:input wire:model="newHolidayName" :label="__('Name')" required />
                    @error('newHolidayName')
                        <p class="mt-1 text-sm text-brand-red">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <flux:input
                        wire:model="newHolidayDate"
                        type="date"
                        :label="__('Date')"
                        min="{{ $batch->year }}-01-01"
                        max="{{ $batch->year }}-12-31"
                        required
                    />
                    @error('newHolidayDate')
                        <p class="mt-1 text-sm text-brand-red">{{ $message }}</p>
                    @enderror
                </div>

                <flux:select wire:model="newHolidayScope" :label="__('Scope')" required>
                    @foreach (['federal', 'state', 'federal_and_state', 'custom'] as $scope)
                        <flux:select.option value="{{ $scope }}">{{ str_replace('_', ' ', $scope) }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="newHolidayType" :label="__('Type')" required>
                    @foreach (['federal', 'state', 'replacement', 'additional', 'custom'] as $type)
                        <flux:select.option value="{{ $type }}">{{ $type }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="md:col-span-2">
                    <flux:checkbox wire:model="newHolidayIsSubjectToChange" :label="__('Subject to change')" />
                </div>

                <div class="md:col-span-2">
                    <flux:textarea wire:model="newHolidaySourceNote" :label="__('Source note')" :placeholder="__('Optional. Defaults to a manual-addition note.')" rows="2" />
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg border border-app-outline p-3">
                <p class="mb-3 text-xs font-semibold tracking-wide text-app-copy-muted uppercase">{{ __('States (optional — same order as the JPM PDF)') }}</p>
                <div class="flex min-w-max gap-1">
                    @foreach ($pdfColumns as $code => $label)
                        <label class="flex w-8 cursor-pointer flex-col items-center gap-1.5" title="{{ $code }} · {{ $stateOptions[$code] }}">
                            <span class="state-grid-heading">{{ $label }}</span>
                            <flux:checkbox
                                wire:model="newHolidayStateCodes"
                                value="{{ $code }}"
                                class="cursor-pointer"
                            />
                            <span class="text-[9px] font-bold tracking-wider text-brand-navy dark:text-slate-300">{{ $code }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="filled" class="cursor-pointer">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="plus" class="cursor-pointer">
                    {{ __('Add holiday') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
