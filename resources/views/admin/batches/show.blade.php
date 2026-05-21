<x-layouts::app :title="__('Batch #:id', ['id' => $batch->id])">
    <div class="admin-page max-w-7xl mx-auto space-y-8">
        <!-- Modernized Header -->
        <div class="admin-header flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-app-surface-card p-6 rounded-2xl border border-app-outline shadow-xs">
            <div>
                <div class="flex items-center gap-3">
                    <p class="app-label text-brand-red">{{ __('Batch Review') }}</p>
                    <span class="app-badge {{ $batch->status === 'published' ? 'app-badge-gold' : ($batch->status === 'confirmed' ? 'app-badge-navy' : 'app-badge-red') }}">{{ $batch->status }}</span>
                </div>
                <h1 class="app-page-title mt-2">{{ __('Batch #:id', ['id' => $batch->id]) }}</h1>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2 text-sm text-app-copy-muted">
                    <span><strong>Source:</strong> {{ $batch->source?->source_name }}</span>
                    <span>•</span>
                    <span class="capitalize"><strong>Method:</strong> {{ str_replace('_', ' ', $batch->import_method) }}</span>
                    <span>•</span>
                    <span><strong>Year:</strong> {{ $batch->year }}</span>
                    <span>•</span>
                    <span><strong>Created:</strong> {{ $batch->created_at->diffForHumans() }}</span>
                    @if ($batch->provider || $batch->model)
                        <span>•</span>
                        <span><strong>AI Model:</strong> {{ $batch->provider }} ({{ $batch->model }})</span>
                    @endif
                </div>
            </div>
            
            @php
                $hasHolidaysMissingStates = $batch->holidays
                    ->where('status', '!=', 'cancelled')
                    ->contains(function ($holiday): bool {
                        return $holiday->stateCodes() === [];
                    });
            @endphp
            
            <div class="flex items-center gap-3">
                <form method="POST" action="{{ route('admin.batches.publish', $batch) }}">
                    @csrf
                    <flux:button type="submit" variant="primary" icon="check-circle" :disabled="$batch->invalid_rows > 0 || $batch->status === 'draft' || $hasHolidaysMissingStates">{{ __('Publish Batch') }}</flux:button>
                </form>
            </div>
        </div>

        @if ($batch->failed_at)
            <div class="app-section border-brand-red/30">
                <p class="app-label text-brand-red">{{ __('Extraction failed') }}</p>
                <p class="mt-2 text-app-copy">{{ $batch->failure_reason }}</p>
            </div>
        @endif

        @if ($isPdfExtractionPending)
            <div class="app-section flex items-center gap-4 border-brand-gold/40">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-gold/10 text-brand-gold">
                    <flux:icon.loading class="size-5" />
                </div>
                <div>
                    <p class="font-semibold text-brand-navy dark:text-white">{{ __('PDF extraction in progress') }}</p>
                    <p class="app-page-copy mt-1">{{ __('Gemini is extracting holiday rows from the source PDF. This page will refresh automatically.') }}</p>
                </div>
            </div>
        @endif

        <!-- Color-coded Stat Cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="admin-stat-card border-l-4 border-l-slate-400 dark:border-l-slate-600">
                <p class="app-label">{{ __('Total Rows') }}</p>
                <p class="mt-4 text-3xl font-extrabold text-brand-navy dark:text-white">{{ $batch->total_rows }}</p>
            </div>
            <div class="admin-stat-card border-l-4 border-l-emerald-500">
                <p class="app-label text-emerald-600 dark:text-emerald-400">{{ __('Valid') }}</p>
                <p class="mt-4 text-3xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ $batch->valid_rows }}</p>
            </div>
            <div class="admin-stat-card border-l-4 border-l-brand-red">
                <p class="app-label text-brand-red">{{ __('Invalid') }}</p>
                <p class="mt-4 text-3xl font-extrabold text-brand-red">{{ $batch->invalid_rows }}</p>
            </div>
            <div class="admin-stat-card border-l-4 border-l-brand-gold">
                <p class="app-label text-amber-500">{{ __('Warnings') }}</p>
                <p class="mt-4 text-3xl font-extrabold text-amber-500">{{ $batch->warning_rows }}</p>
            </div>
        </div>

        <!-- Alpine.js tabbed interface -->
        <div x-data="{ activeTab: 'approvals' }" class="space-y-6">
            <!-- Tabs Navigation -->
            <div class="flex border-b border-app-outline">
                <button
                    type="button"
                    @click="activeTab = 'approvals'"
                    :class="activeTab === 'approvals' ? 'border-brand-red text-brand-red font-semibold' : 'border-transparent text-app-copy-muted hover:text-app-copy'"
                    class="px-4 py-2.5 -mb-px border-b-2 text-sm transition-all focus:outline-hidden cursor-pointer"
                >
                    {{ __('Draft Holidays for Approval') }}
                    <span class="ml-1.5 py-0.5 px-1.5 text-xs bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-full font-medium">
                        {{ $batch->holidays->where('status', 'draft')->count() }}
                    </span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'audit'"
                    :class="activeTab === 'audit' ? 'border-brand-red text-brand-red font-semibold' : 'border-transparent text-app-copy-muted hover:text-app-copy'"
                    class="px-4 py-2.5 -mb-px border-b-2 text-sm transition-all focus:outline-hidden cursor-pointer"
                >
                    {{ __('Row Audit Logs & Validation') }}
                    <span class="ml-1.5 py-0.5 px-1.5 text-xs bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-full font-medium">
                        {{ $batch->importRows->count() }}
                    </span>
                </button>
            </div>

            <!-- Tab Content: Approvals -->
            <div x-show="activeTab === 'approvals'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                <form method="POST" action="{{ route('admin.batches.approve-selected', $batch) }}" class="app-card overflow-hidden">
                    @csrf
                    <div class="flex items-center justify-between border-b border-app-border px-6 py-4 bg-slate-50/50 dark:bg-slate-900/50">
                        <div>
                            <p class="font-semibold text-slate-800 dark:text-slate-200 text-base">{{ __('Holiday Approvals') }}</p>
                            <p class="text-xs text-app-copy-muted mt-1">{{ __('Select draft holidays and confirm their state applicability to approve them.') }}</p>
                        </div>
                        <flux:button type="submit" variant="primary" icon="check">{{ __('Approve Selected') }}</flux:button>
                    </div>
                    @error('holiday_ids')
                        <div class="bg-red-50 dark:bg-red-950/20 border-b border-red-200 dark:border-red-900/50 px-6 py-3">
                            <p class="text-sm text-brand-red font-medium">{{ $message }}</p>
                        </div>
                    @enderror
                    <table class="app-table">
                        <thead>
                            <tr>
                                <th class="w-12">
                                    <input
                                        id="select-all-holidays"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-app-border text-brand-navy focus:ring-brand-navy cursor-pointer"
                                    >
                                </th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Holiday Name') }}</th>
                                <th>{{ __('Scope') }}</th>
                                <th>{{ __('State Applicability') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($batch->holidays as $holiday)
                                @php
                                    $selectedStates = collect(old("state_codes.{$holiday->id}", $holiday->stateCodes()))
                                        ->map(fn (mixed $stateCode): string => (string) $stateCode)
                                        ->all();
                                    $stateErrorKey = "state_codes.{$holiday->id}";
                                @endphp
                                <tr class="hover:bg-slate-50/30 dark:hover:bg-slate-800/10">
                                    <td>
                                        @if ($holiday->status === 'draft')
                                            <input type="checkbox" name="holiday_ids[]" value="{{ $holiday->id }}" class="js-holiday-select h-4 w-4 rounded border-app-border text-brand-navy focus:ring-brand-navy cursor-pointer">
                                        @endif
                                    </td>
                                    <td class="font-mono whitespace-nowrap">{{ $holiday->date->toDateString() }}</td>
                                    <td class="font-semibold text-slate-800 dark:text-slate-200">{{ $holiday->name }}</td>
                                    <td>
                                        <div class="flex flex-wrap gap-2">
                                            <span class="app-badge app-badge-navy">{{ $holiday->scope }}</span>
                                            @if ($holiday->is_subject_to_change)
                                                <span class="app-badge app-badge-gold">{{ __('Subject to change') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-4">
                                        @if ($holiday->status === 'draft')
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="text-[10px] font-bold tracking-wider text-slate-400 uppercase">{{ __('Quick Select:') }}</span>
                                                <button type="button" class="js-select-all-states text-[10px] font-bold text-brand-red hover:underline focus:outline-hidden cursor-pointer" data-id="{{ $holiday->id }}">{{ __('All') }}</button>
                                                <span class="text-slate-300">|</span>
                                                <button type="button" class="js-clear-all-states text-[10px] font-bold text-slate-500 hover:underline focus:outline-hidden cursor-pointer" data-id="{{ $holiday->id }}">{{ __('Clear') }}</button>
                                            </div>
                                        @endif
                                        <div class="flex flex-wrap gap-1 max-w-[340px]">
                                            @foreach ($stateOptions as $stateCode => $stateName)
                                                <label class="cursor-pointer select-none" title="{{ $stateName }}">
                                                    <input
                                                        type="checkbox"
                                                        name="state_codes[{{ $holiday->id }}][]"
                                                        value="{{ $stateCode }}"
                                                        {{ in_array($stateCode, $selectedStates, true) ? 'checked' : '' }}
                                                        {{ $holiday->status !== 'draft' ? 'disabled' : '' }}
                                                        class="peer sr-only js-state-checkbox"
                                                    >
                                                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 peer-checked:bg-brand-navy peer-checked:text-white peer-checked:border-brand-navy dark:peer-checked:bg-white dark:peer-checked:text-brand-navy dark:peer-checked:border-white hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all duration-150 disabled:opacity-50 disabled:pointer-events-none">
                                                        {{ $stateCode }}
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                        @error($stateErrorKey)
                                            <p class="mt-1 text-xs text-brand-red font-medium">{{ $message }}</p>
                                        @enderror
                                        @if ($holiday->stateCodes() === [])
                                            <div class="mt-2 flex items-center gap-1.5 text-[10px] font-semibold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/20 px-2 py-0.5 rounded w-fit">
                                                <flux:icon.exclamation-triangle class="size-3" />
                                                <span>{{ __('Requires manual review') }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="app-badge {{ $holiday->status === 'published' ? 'app-badge-gold' : ($holiday->status === 'confirmed' ? 'app-badge-navy' : 'app-badge-red') }}">{{ $holiday->status }}</span>
                                    </td>
                                    <td class="text-right">
                                        <a class="admin-action-link" href="{{ route('admin.holidays.edit', $holiday) }}">{{ __('Edit') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-app-copy-muted py-8">{{ __('No holidays in this batch.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </form>
            </div>

            <!-- Tab Content: Row Audit Logs -->
            <div x-show="activeTab === 'audit'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                <div class="app-card overflow-hidden">
                    <div class="border-b border-app-border px-6 py-4 bg-slate-50/50 dark:bg-slate-900/50">
                        <p class="font-semibold text-slate-800 dark:text-slate-200 text-base">{{ __('Validation & Raw Row Log') }}</p>
                        <p class="text-xs text-app-copy-muted mt-1">{{ __('Examine row parsing logs, confidence scores, warnings, and errors from the raw input file.') }}</p>
                    </div>
                    <table class="app-table">
                        <thead>
                            <tr>
                                <th class="w-16">{{ __('Row') }}</th>
                                <th class="w-32">{{ __('Status') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('State') }}</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Notes & Warnings') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($batch->importRows as $row)
                                @php
                                    $payload = $row->normalized_payload ?? [];
                                @endphp
                                <tr class="hover:bg-slate-50/30 dark:hover:bg-slate-800/10">
                                    <td class="font-mono">{{ $row->row_number }}</td>
                                    <td>
                                        <span class="app-badge {{ $row->status === 'invalid' ? 'app-badge-red' : ($row->status === 'warning' ? 'app-badge-gold' : 'app-badge-navy') }}">
                                            {{ $row->status }}
                                        </span>
                                    </td>
                                    <td class="font-mono whitespace-nowrap">{{ $payload['date'] ?? '—' }}</td>
                                    <td>
                                        <div class="flex flex-wrap gap-1">
                                            @if (isset($payload['state_codes']) && filled($payload['state_codes']))
                                                @foreach (explode(',', $payload['state_codes']) as $stateCode)
                                                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300">
                                                        {{ trim($stateCode) }}
                                                    </span>
                                                @endforeach
                                            @else
                                                —
                                            @endif
                                        </div>
                                    </td>
                                    <td class="font-semibold text-slate-800 dark:text-slate-200">{{ $payload['name'] ?? '—' }}</td>
                                    <td>
                                        @foreach (($row->errors ?? []) as $error)
                                            <div class="flex items-start gap-1.5 text-brand-red mt-1">
                                                <flux:icon.x-circle class="size-3.5 mt-0.5 shrink-0" />
                                                <span class="text-xs font-medium">{{ $error }}</span>
                                            </div>
                                        @endforeach
                                        @foreach (($row->warnings ?? []) as $warning)
                                            <div class="flex items-start gap-1.5 text-amber-600 dark:text-amber-500 mt-1">
                                                <flux:icon.exclamation-circle class="size-3.5 mt-0.5 shrink-0" />
                                                <span class="text-xs font-medium">{{ $warning }}</span>
                                            </div>
                                        @endforeach
                                        @if ($row->confidence !== null)
                                            <p class="font-mono text-xs text-app-muted mt-2">{{ __('Confidence') }}: {{ $row->confidence }}</p>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-app-copy-muted py-8">{{ __('No row audit entries yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Interaction JS -->
    <script>
        (() => {
            function initBatchShowPage() {
                const selectAllCheckbox = document.getElementById('select-all-holidays');
                if (selectAllCheckbox) {
                    selectAllCheckbox.addEventListener('change', () => {
                        const isChecked = selectAllCheckbox.checked;
                        const holidayCheckboxes = Array.from(document.querySelectorAll('.js-holiday-select'));
                        holidayCheckboxes.forEach((holidayCheckbox) => {
                            holidayCheckbox.checked = isChecked;
                        });
                    });
                }

                // Sync state checkbox changes to row selection
                const stateCheckboxes = document.querySelectorAll('.js-state-checkbox');
                stateCheckboxes.forEach((stateCheckbox) => {
                    stateCheckbox.addEventListener('change', (e) => {
                        const row = e.target.closest('tr');
                        if (!row) return;

                        const rowCheckbox = row.querySelector('.js-holiday-select');
                        if (!rowCheckbox) return;

                        const anyChecked = row.querySelectorAll('.js-state-checkbox:checked').length > 0;
                        rowCheckbox.checked = anyChecked;
                    });
                });

                // Quick select "All" states
                const selectAllButtons = document.querySelectorAll('.js-select-all-states');
                selectAllButtons.forEach((button) => {
                    button.addEventListener('click', (e) => {
                        const row = e.target.closest('tr');
                        if (!row) return;

                        const checkboxes = row.querySelectorAll('.js-state-checkbox');
                        checkboxes.forEach((cb) => {
                            if (!cb.disabled) {
                                cb.checked = true;
                            }
                        });

                        const rowCheckbox = row.querySelector('.js-holiday-select');
                        if (rowCheckbox) {
                            rowCheckbox.checked = true;
                        }
                    });
                });

                // Quick select "None" (Clear) states
                const clearAllButtons = document.querySelectorAll('.js-clear-all-states');
                clearAllButtons.forEach((button) => {
                    button.addEventListener('click', (e) => {
                        const row = e.target.closest('tr');
                        if (!row) return;

                        const checkboxes = row.querySelectorAll('.js-state-checkbox');
                        checkboxes.forEach((cb) => {
                            if (!cb.disabled) {
                                cb.checked = false;
                            }
                        });

                        const rowCheckbox = row.querySelector('.js-holiday-select');
                        if (rowCheckbox) {
                            rowCheckbox.checked = false;
                        }
                    });
                });
            }

            initBatchShowPage();
            document.addEventListener('livewire:navigated', initBatchShowPage);
        })();
    </script>
</x-layouts::app>
