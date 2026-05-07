<x-layouts::app :title="__('Upload Source')">
    <div class="admin-page">
        <div class="admin-header">
            <div>
                <p class="app-label text-brand-red">{{ __('Import Sources') }}</p>
                <h1 class="app-page-title mt-2">{{ __('Upload Source') }}</h1>
                <p class="app-page-copy mt-2">{{ __('Store source metadata, optional file path, and checksum for traceable imports.') }}</p>
            </div>
        </div>

        <form class="app-section max-w-2xl space-y-5" method="POST" action="{{ route('admin.sources.store') }}" enctype="multipart/form-data">
            @csrf

            <flux:input name="year" type="number" min="2000" max="2100" :label="__('Year')" required />
            <flux:input name="source_name" :label="__('Source name')" required />
            <flux:select name="source_type" :label="__('Source type')" required>
                @foreach (['federal_pdf', 'state_page', 'gazette', 'admin_csv', 'manual_entry', 'third_party_reference'] as $type)
                    <flux:select.option value="{{ $type }}">{{ $type }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input name="source_url" type="url" :label="__('Source URL')" />
            <flux:input name="file" type="file" :label="__('File')" />
            <flux:textarea name="notes" :label="__('Notes')" />

            <flux:button type="submit" variant="primary">{{ __('Store source') }}</flux:button>
        </form>
    </div>
</x-layouts::app>
