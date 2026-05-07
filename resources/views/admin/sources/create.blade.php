<x-layouts::app :title="__('Upload Source')">
    <form class="max-w-2xl space-y-4" method="POST" action="{{ route('admin.sources.store') }}" enctype="multipart/form-data">
        @csrf
        <h1 class="text-xl font-semibold">{{ __('Upload Source') }}</h1>

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
</x-layouts::app>
