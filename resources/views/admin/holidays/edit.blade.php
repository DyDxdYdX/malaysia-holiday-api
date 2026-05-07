<x-layouts::app :title="__('Edit Holiday')">
    <form class="max-w-2xl space-y-4" method="POST" action="{{ route('admin.holidays.update', $holiday) }}">
        @csrf
        @method('PUT')
        <h1 class="text-xl font-semibold">{{ __('Edit Holiday') }}</h1>

        <flux:input name="year" type="number" :label="__('Year')" :value="$holiday->year" required />
        <flux:input name="state_code" :label="__('State code')" :value="$holiday->state_code" required />
        <flux:input name="name" :label="__('Name')" :value="$holiday->name" required />
        <flux:input name="date" type="date" :label="__('Date')" :value="$holiday->date->toDateString()" required />
        <flux:input name="scope" :label="__('Scope')" :value="$holiday->scope" required />
        <flux:input name="type" :label="__('Type')" :value="$holiday->type" required />
        <flux:checkbox name="is_subject_to_change" :checked="$holiday->is_subject_to_change" :label="__('Subject to change')" />
        <flux:textarea name="source_note" :label="__('Source note')">{{ $holiday->source_note }}</flux:textarea>

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
        </div>
    </form>

    <form class="mt-4" method="POST" action="{{ route('admin.holidays.reject', $holiday) }}">
        @csrf
        <flux:button type="submit" variant="danger">{{ __('Reject') }}</flux:button>
    </form>
</x-layouts::app>
