<x-layouts::app :title="__('Edit Holiday')">
    <div class="admin-page">
        <div class="admin-header">
            <div>
                <p class="app-label text-brand-red">{{ __('Draft Holiday') }}</p>
                <h1 class="app-page-title mt-2">{{ __('Edit Holiday') }}</h1>
                <p class="app-page-copy mt-2">{{ __('Confirm row details before the batch is published.') }}</p>
            </div>
        </div>

        <section class="app-section max-w-2xl">
            <form class="space-y-5" method="POST" action="{{ route('admin.holidays.update', $holiday) }}">
                @csrf
                @method('PUT')

                <flux:input name="year" type="number" :label="__('Year')" :value="$holiday->year" required />
                <flux:input name="state_code" :label="__('State code')" :value="$holiday->state_code" required />
                <flux:input name="name" :label="__('Name')" :value="$holiday->name" required />
                <flux:input name="date" type="date" :label="__('Date')" :value="$holiday->date->toDateString()" required />
                <flux:input name="scope" :label="__('Scope')" :value="$holiday->scope" required />
                <flux:input name="type" :label="__('Type')" :value="$holiday->type" required />
                <flux:checkbox name="is_subject_to_change" :checked="$holiday->is_subject_to_change" :label="__('Subject to change')" />
                <flux:textarea name="source_note" :label="__('Source note')">{{ $holiday->source_note }}</flux:textarea>

                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary" icon="check">{{ __('Save') }}</flux:button>
                </div>
            </form>

            <form class="mt-4 border-t border-app-outline/70 pt-4" method="POST" action="{{ route('admin.holidays.reject', $holiday) }}">
                @csrf
                <flux:button type="submit" variant="danger" icon="x-mark">{{ __('Reject') }}</flux:button>
            </form>
        </section>
    </div>
</x-layouts::app>
