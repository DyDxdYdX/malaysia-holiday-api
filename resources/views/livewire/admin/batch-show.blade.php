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
                <span class="app-badge app-badge-navy font-semibold px-3 py-1.5 text-sm">{{ __('Published') }}</span>
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

    <!-- Livewire-managed Tabbed Interface -->
    <div class="space-y-6">
        <!-- Session Alerts -->
        @if (session()->has('status'))
            <div class="rounded-lg bg-green-50 p-4 text-sm text-green-800 dark:bg-green-950/30 dark:text-green-400 border border-green-200 dark:border-green-800/30 flex items-center gap-2">
                <flux:icon.check-circle class="size-5 shrink-0" />
                <div>{{ session('status') }}</div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="rounded-lg bg-red-50 p-4 text-sm text-brand-red dark:bg-red-950/30 dark:text-red-400 border border-red-200 dark:border-red-800/30 flex items-center gap-2">
                <flux:icon.exclamation-circle class="size-5 shrink-0" />
                <div>{{ session('error') }}</div>
            </div>
        @endif

        <!-- Tabs Navigation -->
        <div class="flex border-b border-app-outline">
            <button
                type="button"
                wire:click="$set('activeTab', 'approvals')"
                class="px-4 py-2.5 -mb-px border-b-2 text-sm transition-all focus:outline-hidden cursor-pointer {{ $activeTab === 'approvals' ? 'border-brand-red text-brand-red font-semibold' : 'border-transparent text-app-copy-muted hover:text-app-copy' }}"
            >
                {{ __('Holidays for Review') }}
                <span class="ml-1.5 py-0.5 px-1.5 text-xs bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-full font-medium">
                    {{ $batch->holidays->whereIn('status', ['draft', 'confirmed'])->count() }}
                </span>
            </button>
            <button
                type="button"
                wire:click="$set('activeTab', 'audit')"
                class="px-4 py-2.5 -mb-px border-b-2 text-sm transition-all focus:outline-hidden cursor-pointer {{ $activeTab === 'audit' ? 'border-brand-red text-brand-red font-semibold' : 'border-transparent text-app-copy-muted hover:text-app-copy' }}"
            >
                {{ __('Row Audit Logs & Validation') }}
                <span class="ml-1.5 py-0.5 px-1.5 text-xs bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-full font-medium">
                    {{ $batch->importRows->count() }}
                </span>
            </button>
        </div>

        <!-- Tab Content: Approvals -->
        @if ($activeTab === 'approvals')
            <div class="app-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-app-border px-4 py-3">
                    <p class="app-label">{{ __('Holiday approvals') }}</p>
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

                <table class="app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('States') }}</th>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Flags') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($batch->holidays as $holiday)
                            <tr wire:key="holiday-row-{{ $holiday->id }}">
                                <td class="font-mono">{{ $holiday->date->toDateString() }}</td>
                                <td class="min-w-64">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @php
                                            $stateCodes = $holiday->stateCodes();
                                            $allCodes = \App\Support\MalaysiaStates::codes();
                                            $isAllStates = count($stateCodes) === count($allCodes);
                                        @endphp

                                        @if (empty($stateCodes))
                                            <span class="text-xs font-semibold text-brand-gold">{{ __('State applicability requires manual review.') }}</span>
                                        @elseif ($isAllStates)
                                            <span class="app-badge app-badge-navy font-semibold">{{ __('All States') }}</span>
                                        @else
                                            @foreach ($stateCodes as $stateCode)
                                                <span class="app-badge app-badge-navy">{{ $stateCode }}</span>
                                            @endforeach
                                        @endif

                                        @if ($holiday->status === 'draft' && $batch->status !== 'published')
                                            <flux:dropdown>
                                                <flux:button size="xs" variant="subtle" icon="pencil-square" class="cursor-pointer ml-1" square />
                                                <flux:menu class="max-h-64 overflow-y-auto">
                                                    <div class="px-3 py-1.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                                        {{ __('Select States') }}
                                                    </div>
                                                    <flux:menu.item wire:click="selectAllStates({{ $holiday->id }})" icon="check-circle" class="cursor-pointer">
                                                        {{ __('Select All') }}
                                                    </flux:menu.item>
                                                    <flux:menu.item wire:click="clearStates({{ $holiday->id }})" icon="x-circle" class="cursor-pointer">
                                                        {{ __('Clear All') }}
                                                    </flux:menu.item>
                                                    <flux:menu.separator />
                                                    @foreach ($stateOptions as $code => $name)
                                                        <flux:menu.checkbox
                                                            :checked="in_array($code, $stateCodes, true)"
                                                            wire:click="toggleState({{ $holiday->id }}, '{{ $code }}')"
                                                            class="cursor-pointer"
                                                        >
                                                            {{ $code }} - {{ $name }}
                                                        </flux:menu.checkbox>
                                                    @endforeach
                                                </flux:menu>
                                            </flux:dropdown>
                                        @endif
                                    </div>
                                    @error("holiday-{$holiday->id}")
                                        <p class="mt-2 text-sm text-brand-red">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td>{{ $holiday->name }}</td>
                                <td>
                                    <div class="flex flex-wrap gap-2">
                                        <span class="app-badge app-badge-navy">{{ $holiday->scope }}</span>
                                        @if ($holiday->is_subject_to_change)
                                            <span class="app-badge app-badge-gold">{{ __('Subject to change') }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="app-badge {{ $holiday->status === 'published' ? 'app-badge-gold' : ($holiday->status === 'confirmed' ? 'app-badge-navy' : 'app-badge-red') }}">
                                        {{ $holiday->status }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2 justify-end">
                                        @if ($batch->status !== 'published')
                                            @if ($holiday->status === 'draft')
                                                <flux:button 
                                                    size="xs" 
                                                    variant="primary" 
                                                    wire:click="approveHoliday({{ $holiday->id }})"
                                                    class="cursor-pointer"
                                                >
                                                    {{ __('Approve') }}
                                                </flux:button>
                                                <flux:button 
                                                    size="xs" 
                                                    variant="danger" 
                                                    wire:click="rejectHoliday({{ $holiday->id }})"
                                                    class="cursor-pointer"
                                                >
                                                    {{ __('Reject') }}
                                                </flux:button>
                                            @elseif ($holiday->status === 'confirmed')
                                                <flux:button 
                                                    size="xs" 
                                                    variant="danger" 
                                                    wire:click="rejectHoliday({{ $holiday->id }})"
                                                    class="cursor-pointer"
                                                >
                                                    {{ __('Reject') }}
                                                </flux:button>
                                            @elseif ($holiday->status === 'cancelled')
                                                <flux:button 
                                                    size="xs" 
                                                    variant="primary" 
                                                    wire:click="approveHoliday({{ $holiday->id }})"
                                                    class="cursor-pointer"
                                                >
                                                    {{ __('Approve') }}
                                                </flux:button>
                                            @endif
                                        @endif
                                        <a class="admin-action-link" href="{{ route('admin.holidays.edit', $holiday) }}" wire:navigate>{{ __('Edit') }}</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">{{ __('No holidays in this batch.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Tab Content: Row Audit Logs -->
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
</div>
