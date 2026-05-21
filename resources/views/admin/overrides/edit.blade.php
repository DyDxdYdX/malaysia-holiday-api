<x-layouts::app :title="__('Edit Override')">
    <div class="admin-page">
        <div class="admin-header">
            <div>
                <p class="app-label text-brand-red">{{ __('Manual Override') }}</p>
                <h1 class="app-page-title mt-2">{{ __('Edit Override') }}</h1>
                <p class="app-page-copy mt-2">{{ __('Update correction details for this override entry.') }}</p>
            </div>
        </div>

        <form class="app-form-shell" method="POST" action="{{ route('admin.overrides.update', $override) }}">
            @csrf
            @method('PUT')

            <div class="app-form-grid">
                <flux:select class="app-form-field-full" name="holiday_id" :label="__('Published holiday')">
                    <flux:select.option value="">{{ __('None') }}</flux:select.option>
                    @foreach ($holidays as $holiday)
                        <flux:select.option
                            value="{{ $holiday->id }}"
                            :selected="(string) old('holiday_id', $override->holiday_id) === (string) $holiday->id"
                        >
                            {{ $holiday->date->toDateString() }} · {{ implode(', ', $holiday->stateCodes()) }} · {{ $holiday->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input name="year" type="number" min="2000" max="2100" :label="__('Year')" :value="old('year', $override->year)" required />
                <flux:input name="date" type="date" :label="__('Date')" :value="old('date', $override->date->toDateString())" required />
                <flux:select name="state_code" :label="__('State')" required>
                    @foreach ($stateOptions as $stateCode => $stateName)
                        <flux:select.option
                            value="{{ $stateCode }}"
                            :selected="old('state_code', $override->state_code) === $stateCode"
                        >
                            {{ $stateCode }} · {{ $stateName }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select name="action" :label="__('Action')" required>
                    @foreach (['add', 'remove', 'replace', 'rename', 'mark_subject_to_change'] as $action)
                        <flux:select.option value="{{ $action }}" :selected="old('action', $override->action) === $action">{{ $action }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input class="app-form-field-full" name="name" :label="__('Name')" :value="old('name', $override->name)" required />
                <flux:textarea class="app-form-field-full" name="reason" :label="__('Reason')" required>{{ old('reason', $override->reason) }}</flux:textarea>
                <flux:input class="app-form-field-full" name="source_url" type="url" :label="__('Source URL')" :value="old('source_url', $override->source_url)" />

                <div class="app-form-actions">
                    <flux:text>{{ __('Use precise reasons to keep the audit trail clear.') }}</flux:text>
                    <flux:button type="submit" variant="primary" icon="check">{{ __('Update override') }}</flux:button>
                </div>
            </div>
        </form>
    </div>
</x-layouts::app>
