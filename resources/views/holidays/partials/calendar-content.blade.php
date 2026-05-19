<div class="admin-page">
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
            <flux:input name="year" type="number" min="2000" max="2100" :label="__('Year')" :value="$filters['year']" />
            <flux:input name="state_code" :label="__('State code')" :value="$filters['state_code']" />
            <flux:select name="scope" :label="__('Scope')">
                <flux:select.option value="">{{ __('All') }}</flux:select.option>
                @foreach (['federal', 'state', 'custom'] as $scopeOption)
                    <flux:select.option value="{{ $scopeOption }}" :selected="$filters['scope'] === $scopeOption">{{ ucfirst($scopeOption) }}</flux:select.option>
                @endforeach
            </flux:select>
            <div class="md:col-span-2 flex items-end gap-2">
                <flux:button type="submit" variant="primary" icon="funnel">{{ __('Filter') }}</flux:button>
                <flux:button :href="$isAdminView ? route('admin.holidays.calendar') : route('holidays.calendar')" variant="ghost" icon="x-mark" wire:navigate>{{ __('Reset') }}</flux:button>
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

    <section class="grid gap-4 lg:grid-cols-3">
        @foreach ($months as $month)
            <article class="app-card overflow-hidden">
                <header class="border-b border-app-outline px-4 py-3">
                    <h2 class="font-bold text-brand-navy dark:text-white">{{ $month['month_name'] }}</h2>
                </header>
                <div class="grid grid-cols-7 border-b border-app-outline bg-app-surface-low/50 text-[10px] font-bold tracking-widest text-app-copy-muted uppercase">
                    @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday)
                        <div class="border-r border-app-outline px-2 py-1 last:border-r-0">{{ $weekday }}</div>
                    @endforeach
                </div>
                @foreach ($month['weeks'] as $week)
                    <div class="grid grid-cols-7">
                        @foreach ($week as $day)
                            <div @class([
                                'min-h-28 border-r border-b border-app-outline p-2 text-xs last:border-r-0',
                                'bg-app-surface-low/20 text-app-copy-muted' => ! $day['in_month'],
                            ])>
                                <p class="font-semibold">{{ $day['date']->day }}</p>
                                <div class="mt-1 space-y-1">
                                    @foreach ($day['holidays'] as $holiday)
                                        <div class="rounded-md bg-brand-red/10 px-1.5 py-1 text-[10px] leading-tight text-brand-red">
                                            <p class="font-semibold">{{ $holiday->name }}</p>
                                            <p>{{ $holiday->state_code }} · {{ $holiday->scope }}</p>
                                            @if ($isAdminView)
                                                <p class="uppercase tracking-wider">{{ $holiday->status }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </article>
        @endforeach
    </section>
</div>
