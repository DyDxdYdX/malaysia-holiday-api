<x-layouts::app :title="__('Holiday Management')">
    <div class="admin-page">
        <div class="admin-header">
            <div>
                <p class="app-label text-brand-red">{{ __('Published Calendar') }}</p>
                <h1 class="app-page-title mt-2">{{ __('Holiday Management') }}</h1>
                <p class="app-page-copy mt-2">{{ __('Search and filter holiday records, then create overrides when corrections are needed.') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:button :href="route('admin.holidays.calendar')" variant="ghost" icon="calendar" wire:navigate>{{ __('Calendar View') }}</flux:button>
                <flux:button :href="route('admin.holidays.create')" variant="primary" icon="plus" wire:navigate>{{ __('Add Manual Holiday') }}</flux:button>
            </div>
        </div>

        <section class="app-section space-y-4">
            <form method="GET" action="{{ route('admin.holidays.index') }}" class="grid gap-3 md:grid-cols-5">
                <flux:input name="q" :label="__('Search')" :value="$filters['q']" :placeholder="__('Name, state, date, note...')" />
                <flux:input name="year" type="number" min="2000" max="2100" :label="__('Year')" :value="$filters['year']" />
                <flux:input name="state_code" :label="__('State code')" :value="$filters['state_code']" />
                <flux:select name="scope" :label="__('Scope')">
                    <flux:select.option value="">{{ __('All') }}</flux:select.option>
                    @foreach (['federal', 'state', 'custom'] as $scopeOption)
                        <flux:select.option value="{{ $scopeOption }}" :selected="$filters['scope'] === $scopeOption">{{ ucfirst($scopeOption) }}</flux:select.option>
                    @endforeach
                </flux:select>
                <div class="flex items-end gap-2">
                    <flux:button type="submit" variant="primary" icon="funnel">{{ __('Filter') }}</flux:button>
                    <flux:button :href="route('admin.holidays.index')" variant="ghost" icon="x-mark" wire:navigate>{{ __('Reset') }}</flux:button>
                </div>
            </form>
        </section>

        <div class="app-card overflow-hidden">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Day') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('State') }}</th>
                        <th>{{ __('Scope') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Subject to change') }}</th>
                        <th>{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($holidays as $holiday)
                        <tr>
                            <td class="font-mono">{{ $holiday->date->toDateString() }}</td>
                            <td>{{ $holiday->day_name }}</td>
                            <td>{{ $holiday->name }}</td>
                            <td><span class="app-badge app-badge-navy">{{ $holiday->state_code }}</span></td>
                            <td>{{ $holiday->scope }}</td>
                            <td>{{ $holiday->type }}</td>
                            <td>{{ $holiday->status }}</td>
                            <td>
                                @if ($holiday->is_subject_to_change)
                                    <span class="app-badge app-badge-gold">{{ __('Yes') }}</span>
                                @else
                                    <span class="app-badge">{{ __('No') }}</span>
                                @endif
                            </td>
                            <td>
                                <a
                                    class="admin-action-link"
                                    href="{{ route('admin.overrides.create', ['holiday_id' => $holiday->id]) }}"
                                >
                                    {{ __('Create Override') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">{{ __('No holidays match the current filters.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $holidays->links() }}
    </div>
</x-layouts::app>
