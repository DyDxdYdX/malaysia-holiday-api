<x-layouts::app :title="__('Edit Holiday')">
    <div class="admin-page">
        <div class="admin-header">
            <div>
                <p class="app-label text-brand-red">{{ __('Draft Holiday') }}</p>
                <h1 class="app-page-title mt-2">{{ __('Edit Holiday') }}</h1>
                <p class="app-page-copy mt-2">{{ __('Confirm row details before the batch is published.') }}</p>
            </div>
        </div>

        <section class="max-w-4xl">
            <form class="app-form-shell" method="POST" action="{{ route('admin.holidays.update', $holiday) }}">
                @csrf
                @method('PUT')

                <div class="app-form-grid">
                    <flux:input name="year" type="number" :label="__('Year')" :value="$holiday->year" required />
                    <flux:input name="date" type="date" :label="__('Date')" :value="$holiday->date->toDateString()" required />
                    <fieldset class="app-form-field-full">
                        <legend class="mb-2 text-sm font-semibold text-app-copy">{{ __('States') }}</legend>
                        @php
                            $selectedStates = collect(old('state_codes', $holiday->stateCodes()))
                                ->map(fn (mixed $stateCode): string => (string) $stateCode)
                                ->all();
                        @endphp
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($stateOptions as $stateCode => $stateName)
                                <flux:field variant="inline">
                                    <flux:checkbox
                                        name="state_codes[]"
                                        value="{{ $stateCode }}"
                                        :checked="in_array($stateCode, $selectedStates, true)"
                                    />
                                    <flux:label>{{ $stateCode }} · {{ $stateName }}</flux:label>
                                </flux:field>
                            @endforeach
                        </div>
                        @error('state_codes')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </fieldset>
                    <flux:input class="app-form-field-full" name="name" :label="__('Name')" :value="$holiday->name" required />
                    <flux:input name="scope" :label="__('Scope')" :value="$holiday->scope" required />
                    <flux:input name="type" :label="__('Type')" :value="$holiday->type" required />
                    <flux:checkbox class="app-form-field-full" name="is_subject_to_change" :checked="$holiday->is_subject_to_change" :label="__('Subject to change')" />
                    <flux:textarea class="app-form-field-full" name="source_note" :label="__('Source note')">{{ $holiday->source_note }}</flux:textarea>

                    <div class="app-form-actions">
                        <flux:text>{{ __('Review and confirm before publishing changes.') }}</flux:text>
                        <flux:button type="submit" variant="primary" icon="check">{{ __('Save') }}</flux:button>
                    </div>
                </div>
            </form>

            <form class="mt-5 rounded-2xl border border-brand-red/20 bg-brand-red/5 p-4" method="POST" action="{{ route('admin.holidays.reject', $holiday) }}">
                @csrf
                <div class="flex items-center justify-between gap-3">
                    <flux:text>{{ __('Rejecting removes this draft from the publish flow.') }}</flux:text>
                    <flux:button type="submit" variant="danger" icon="x-mark">{{ __('Reject') }}</flux:button>
                </div>
            </form>
        </section>
    </div>
</x-layouts::app>
