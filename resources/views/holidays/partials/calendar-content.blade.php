@php
    $allStateCodes = \App\Support\MalaysiaStates::codes();
@endphp

<div @class([
    'admin-page' => $isAdminView,
    'space-y-6 lg:space-y-8' => ! $isAdminView,
])>
    <div class="admin-header">
        <div>
            <p class="app-label text-brand-red">{{ $isAdminView ? __('Admin Calendar') : __('Public Calendar') }}</p>
            <h1 class="app-page-title mt-2">{{ $title }}</h1>
            <p class="app-page-copy mt-2">{{ $subtitle }}</p>
        </div>
        @if ($isAdminView)
            <flux:button :href="route('admin.holidays.index')" variant="ghost" icon="table-cells" wire:navigate>{{ __('Open Table View') }}</flux:button>
        @endif
    </div>

    <section class="app-section space-y-4">
        <form method="GET" action="{{ $formAction }}" class="grid gap-3 md:grid-cols-5">
            <input type="hidden" name="month" value="{{ $filters['month'] }}" />

            {{-- Year Selector Dropdown --}}
            <flux:select name="year" :label="__('Year')">
                @foreach (range(now()->year - 3, now()->year + 4) as $yr)
                    <flux:select.option value="{{ $yr }}" :selected="(int)$filters['year'] === $yr">{{ $yr }}</flux:select.option>
                @endforeach
            </flux:select>

            {{-- State Selector Dropdown --}}
            <flux:select name="state_code" :label="__('State')">
                <flux:select.option value="">{{ __('All States') }}</flux:select.option>
                @foreach (\App\Support\MalaysiaStates::options() as $stateCode => $stateName)
                    <flux:select.option value="{{ $stateCode }}" :selected="$filters['state_code'] === $stateCode">
                        {{ $stateName }} ({{ $stateCode }})
                    </flux:select.option>
                @endforeach
            </flux:select>

            {{-- Scope Selector Dropdown --}}
            <flux:select name="scope" :label="__('Scope')">
                <flux:select.option value="">{{ __('All Scopes') }}</flux:select.option>
                @foreach (['federal', 'state', 'custom'] as $scopeOption)
                    <flux:select.option value="{{ $scopeOption }}" :selected="$filters['scope'] === $scopeOption">{{ ucfirst($scopeOption) }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="md:col-span-2 flex items-end gap-2">
                <flux:button type="submit" variant="primary" class="w-full md:w-auto" icon="funnel">{{ __('Filter') }}</flux:button>
                <flux:button :href="$isAdminView ? route('admin.holidays.calendar') : route('holidays.calendar')" variant="ghost" class="w-full md:w-auto" icon="x-mark" wire:navigate>{{ __('Reset') }}</flux:button>
            </div>
        </form>

        @if ($isAdminView)
            <div class="flex flex-wrap gap-2 text-xs">
                <span class="app-badge app-badge-navy">{{ __('Admin view: all statuses') }}</span>
                <span class="app-badge">{{ __('published') }}</span>
                <span class="app-badge">{{ __('confirmed') }}</span>
                <span class="app-badge">{{ __('cancelled') }}</span>
                <span class="app-badge">{{ __('pending') }}</span>
            </div>
        @endif
    </section>

    @if (! $hasAnyHoliday)
        <div class="app-card p-6">
            <p class="app-page-copy">{{ __('No holidays match the selected year and filters.') }}</p>
        </div>
    @endif

    @php
        $monthNumber = $month['month_number'];
        $previousMonth = $monthNumber > 1 ? $monthNumber - 1 : null;
        $nextMonth = $monthNumber < 12 ? $monthNumber + 1 : null;
    @endphp

    <section class="space-y-4">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            {{-- Quick Month Switcher Tab-Bar --}}
            <div class="flex flex-wrap gap-1">
                @foreach (range(1, 12) as $m)
                    @php
                        $mName = \Illuminate\Support\Carbon::create(2026, $m, 1)->format('M');
                        $isActive = (int)$filters['month'] === $m;
                    @endphp
                    <flux:button
                        :href="$formAction.'?'.http_build_query([
                            'year' => $filters['year'],
                            'month' => $m,
                            'state_code' => $filters['state_code'],
                            'scope' => $filters['scope'],
                        ])"
                        :variant="$isActive ? 'primary' : 'ghost'"
                        size="sm"
                        wire:navigate
                        class="text-xs font-semibold !px-2.5 !py-1"
                    >
                        {{ __($mName) }}
                    </flux:button>
                @endforeach
            </div>

            <div class="flex items-center gap-2 self-end lg:self-auto">
                @if ($previousMonth)
                    <flux:button
                        :href="$formAction.'?'.http_build_query([
                            'year' => $filters['year'],
                            'month' => $previousMonth,
                            'state_code' => $filters['state_code'],
                            'scope' => $filters['scope'],
                        ])"
                        variant="ghost"
                        icon="chevron-left"
                        wire:navigate
                        size="sm"
                    >{{ __('Previous') }}</flux:button>
                @endif

                @if ($nextMonth)
                    <flux:button
                        :href="$formAction.'?'.http_build_query([
                            'year' => $filters['year'],
                            'month' => $nextMonth,
                            'state_code' => $filters['state_code'],
                            'scope' => $filters['scope'],
                        ])"
                        variant="ghost"
                        icon:trailing="chevron-right"
                        wire:navigate
                        size="sm"
                    >{{ __('Next') }}</flux:button>
                @endif
            </div>
        </div>

        <article @class([
            'app-card overflow-hidden',
            'shadow-sm ring-1 ring-brand-navy/5' => ! $isAdminView,
        ])>
            <header class="border-b border-app-outline px-4 py-3 bg-app-surface-low/50 flex items-center justify-between">
                <h2 class="font-bold text-brand-navy dark:text-white">{{ $month['month_name'] }} {{ $filters['year'] }}</h2>
                @if(! $isAdminView)
                    <span class="app-badge app-badge-navy">{{ __('Public Calendar') }}</span>
                @else
                    <span class="app-badge app-badge-red">{{ __('Admin Console') }}</span>
                @endif
            </header>

            <div class="grid grid-cols-7 border-b border-app-outline bg-app-surface-low/30 text-[10px] font-bold tracking-widest text-app-copy-muted uppercase">
                @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday)
                    <div class="border-r border-app-outline px-3 py-2 last:border-r-0">{{ $weekday }}</div>
                @endforeach
            </div>

            @foreach ($month['weeks'] as $week)
                <div class="grid grid-cols-7">
                    @foreach ($week as $day)
                        @php
                            $isToday = $day['in_month'] && $day['date']->isToday();
                        @endphp
                        <div @class([
                            'min-h-28 border-r border-b border-app-outline p-2 text-xs last:border-r-0 transition-colors',
                            'bg-app-surface-low/10 text-app-copy-muted/70' => ! $day['in_month'],
                            'bg-app-surface-card hover:bg-app-surface-low/20' => $day['in_month'],
                            'ring-1 ring-inset ring-brand-red/20 bg-brand-red/5' => $isToday,
                            'md:min-h-32' => ! $isAdminView,
                        ])>
                            <div class="flex items-center justify-between mb-1.5">
                                <span @class([
                                    'font-bold text-sm',
                                    'text-brand-red' => $isToday,
                                    'text-app-copy-muted' => ! $day['in_month'],
                                    'text-brand-navy/90 dark:text-slate-200' => $day['in_month'] && !$isToday,
                                ])>
                                    {{ $day['date']->day }}
                                </span>
                                @if($isToday)
                                    <span class="app-badge app-badge-red !px-1.5 !py-0.5 text-[8px]">{{ __('Today') }}</span>
                                @endif
                            </div>

                            <div class="mt-1 space-y-1.5">
                                @foreach ($day['holidays'] as $holiday)
                                    @php
                                        $stateCodes = $holiday->stateCodes();
                                        $isAllStates = count($stateCodes) === count($allStateCodes);
                                        $stateLabel = $isAllStates ? __('All States') : implode(', ', $stateCodes);

                                        // Scope & status color coding
                                        if ($holiday->status === 'cancelled') {
                                            $badgeClass = 'bg-zinc-100 text-zinc-400 line-through border border-zinc-200 dark:bg-zinc-900/50 dark:text-zinc-600 dark:border-zinc-800';
                                        } elseif ($holiday->status === 'pending') {
                                            $badgeClass = 'bg-amber-500/10 text-amber-700 border border-amber-500/20 dark:text-amber-400';
                                        } elseif ($holiday->scope === 'federal') {
                                            $badgeClass = 'bg-brand-red/5 text-brand-red border border-brand-red/10 dark:bg-brand-red/10 dark:text-brand-red-bright dark:border-brand-red/20';
                                        } elseif ($holiday->type === 'replacement' || $holiday->type === 'additional') {
                                            $badgeClass = 'bg-amber-500/10 text-amber-800 border border-amber-500/20 dark:text-amber-400 dark:bg-amber-500/5 dark:border-amber-500/15';
                                        } else {
                                            $badgeClass = 'bg-brand-navy/5 text-brand-navy border border-brand-navy/10 dark:bg-white/5 dark:text-slate-300 dark:border-white/10';
                                        }
                                    @endphp

                                    <div class="rounded-lg p-2 text-[10px] leading-tight shadow-3xs {{ $badgeClass }} transition-all hover:scale-[1.02] hover:shadow-xs">
                                        <p class="font-bold tracking-tight">{{ $holiday->name }}</p>
                                        <p class="mt-1 text-[9px] opacity-80">{{ $stateLabel }} · {{ $holiday->scope }}</p>
                                        @if ($isAdminView)
                                            <div class="mt-1.5 flex items-center justify-between border-t border-current/15 pt-1 text-[8px] font-bold uppercase tracking-wider">
                                                <span>{{ $holiday->status }}</span>
                                                <a href="{{ route('admin.holidays.edit', $holiday) }}" class="text-brand-red hover:underline" wire:navigate>{{ __('Edit') }}</a>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </article>
    </section>
</div>

