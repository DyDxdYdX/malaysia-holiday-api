<x-layouts::app :title="__('Upload Source')">
    <div class="admin-page">
        <div class="admin-header">
            <div>
                <p class="app-label text-brand-red">{{ __('Import Sources') }}</p>
                <h1 class="app-page-title mt-2">{{ __('Upload Source') }}</h1>
                <p class="app-page-copy mt-2">{{ __('Store source metadata, optional file path, and checksum for traceable imports.') }}</p>
            </div>
        </div>

        <form class="app-form-shell" method="POST" action="{{ route('admin.sources.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="app-form-grid">
                <flux:input name="year" type="number" min="2000" max="2100" :label="__('Year')" required />
                <flux:input name="source_name" :label="__('Source name')" required />
            @php
                $sourceTypes = [
                    'federal_pdf' => 'Federal PDF',
                    'state_page' => 'State Page',
                    'gazette' => 'Gazette',
                    'admin_csv' => 'Admin CSV',
                    'manual_entry' => 'Manual Entry',
                    'third_party_reference' => 'Third Party Reference',
                ];
            @endphp

                <flux:select name="source_type" :label="__('Source type')" required>
                    @foreach ($sourceTypes as $value => $label)
                        <flux:select.option value="{{ $value }}">
                            {{ $label }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input name="source_url" type="url" :label="__('Source URL')" />
                <flux:input class="app-form-field-full" name="file" type="file" :label="__('File')" />
                <flux:textarea class="app-form-field-full" name="notes" :label="__('Notes')" />

                <div class="app-form-actions">
                    <flux:text>{{ __('Attach a file for PDF extraction or keep metadata only for web references.') }}</flux:text>
                    <flux:button type="submit" variant="primary">{{ __('Store source') }}</flux:button>
                </div>
            </div>
        </form>
    </div>
</x-layouts::app>
