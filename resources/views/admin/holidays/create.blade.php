<x-layouts::app :title="__('Add Manual Holiday')">
    <div class="admin-page">
        <div class="admin-header">
            <div>
                <p class="app-label text-brand-red">{{ __('Manual Calendar Entry') }}</p>
                <h1 class="app-page-title mt-2">{{ __('Add Manual Holiday') }}</h1>
                <p class="app-page-copy mt-2">{{ __('Create a published holiday record for ad-hoc corrections or additions.') }}</p>
            </div>
        </div>

        <form class="app-section max-w-2xl space-y-5" method="POST" action="{{ route('admin.holidays.store') }}">
            @csrf

            <flux:input name="year" type="number" min="2000" max="2100" :label="__('Year')" :value="old('year')" required />
            <flux:input name="state_codes" :label="__('State codes (comma-separated)')" :value="old('state_codes')" required />
            <flux:input name="name" :label="__('Name')" :value="old('name')" required />
            <flux:input name="date" type="date" :label="__('Date')" :value="old('date')" required />

            <flux:select name="scope" :label="__('Scope')" required>
                @foreach (['custom', 'federal', 'state'] as $scope)
                    <flux:select.option value="{{ $scope }}" :selected="old('scope', 'custom') === $scope">{{ ucfirst($scope) }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select name="type" :label="__('Type')" required>
                @foreach (['custom', 'federal', 'state', 'replacement', 'additional'] as $type)
                    <flux:select.option value="{{ $type }}" :selected="old('type', 'custom') === $type">{{ ucfirst($type) }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:checkbox name="is_subject_to_change" :checked="(bool) old('is_subject_to_change')" :label="__('Subject to change')" />
            <flux:textarea name="source_note" :label="__('Source note')">{{ old('source_note') }}</flux:textarea>

            <flux:button type="submit" variant="primary" icon="check">{{ __('Save holiday') }}</flux:button>
        </form>
    </div>
</x-layouts::app>
