<x-layouts::app :title="__('Create Override')">
    <div class="admin-page">
        <div class="admin-header">
            <div>
                <p class="app-label text-brand-red">{{ __('Manual Override') }}</p>
                <h1 class="app-page-title mt-2">{{ __('Create Override') }}</h1>
                <p class="app-page-copy mt-2">{{ __('Apply a published holiday correction while keeping the import history intact.') }}</p>
            </div>
        </div>

        <form class="app-section max-w-2xl space-y-5" method="POST" action="{{ route('admin.overrides.store') }}">
            @csrf

            <flux:select name="holiday_id" :label="__('Published holiday')">
                <flux:select.option value="">{{ __('None') }}</flux:select.option>
                @foreach ($holidays as $holiday)
                    <flux:select.option value="{{ $holiday->id }}">{{ $holiday->date->toDateString() }} · {{ $holiday->state_code }} · {{ $holiday->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input name="year" type="number" min="2000" max="2100" :label="__('Year')" required />
            <flux:input name="state_code" :label="__('State code')" required />
            <flux:input name="name" :label="__('Name')" required />
            <flux:input name="date" type="date" :label="__('Date')" required />
            <flux:select name="action" :label="__('Action')" required>
                @foreach (['add', 'remove', 'replace', 'rename', 'mark_subject_to_change'] as $action)
                    <flux:select.option value="{{ $action }}">{{ $action }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:textarea name="reason" :label="__('Reason')" required />
            <flux:input name="source_url" type="url" :label="__('Source URL')" />

            <flux:button type="submit" variant="primary" icon="check">{{ __('Apply override') }}</flux:button>
        </form>
    </div>
</x-layouts::app>
