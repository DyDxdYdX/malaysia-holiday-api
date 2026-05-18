<x-layouts::app :title="__('Edit Override')">
    <div class="admin-page">
        <div class="admin-header">
            <div>
                <p class="app-label text-brand-red">{{ __('Manual Override') }}</p>
                <h1 class="app-page-title mt-2">{{ __('Edit Override') }}</h1>
                <p class="app-page-copy mt-2">{{ __('Update correction details for this override entry.') }}</p>
            </div>
        </div>

        <form class="app-section max-w-2xl space-y-5" method="POST" action="{{ route('admin.overrides.update', $override) }}">
            @csrf
            @method('PUT')

            <flux:select name="holiday_id" :label="__('Published holiday')">
                <flux:select.option value="">{{ __('None') }}</flux:select.option>
                @foreach ($holidays as $holiday)
                    <flux:select.option
                        value="{{ $holiday->id }}"
                        :selected="(string) old('holiday_id', $override->holiday_id) === (string) $holiday->id"
                    >
                        {{ $holiday->date->toDateString() }} · {{ $holiday->state_code }} · {{ $holiday->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:input name="year" type="number" min="2000" max="2100" :label="__('Year')" :value="old('year', $override->year)" required />
            <flux:input name="state_code" :label="__('State code')" :value="old('state_code', $override->state_code)" required />
            <flux:input name="name" :label="__('Name')" :value="old('name', $override->name)" required />
            <flux:input name="date" type="date" :label="__('Date')" :value="old('date', $override->date->toDateString())" required />
            <flux:select name="action" :label="__('Action')" required>
                @foreach (['add', 'remove', 'replace', 'rename', 'mark_subject_to_change'] as $action)
                    <flux:select.option value="{{ $action }}" :selected="old('action', $override->action) === $action">{{ $action }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:textarea name="reason" :label="__('Reason')" required>{{ old('reason', $override->reason) }}</flux:textarea>
            <flux:input name="source_url" type="url" :label="__('Source URL')" :value="old('source_url', $override->source_url)" />

            <div class="flex items-center gap-3">
                <flux:button type="submit" variant="primary" icon="check">{{ __('Update override') }}</flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
