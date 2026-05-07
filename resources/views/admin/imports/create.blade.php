<x-layouts::app :title="__('Import CSV')">
    <div class="admin-page">
        <div class="admin-header">
            <div>
                <p class="app-label text-brand-red">{{ __('CSV Import') }}</p>
                <h1 class="app-page-title mt-2">{{ __('Import CSV') }}</h1>
                <p class="app-page-copy mt-2">{{ $source->source_name }} · {{ $source->year }}</p>
            </div>
        </div>

        <form class="app-section max-w-2xl space-y-5" method="POST" action="{{ route('admin.sources.import.store', $source) }}" enctype="multipart/form-data">
            @csrf

            <flux:input name="file" type="file" :label="__('CSV file')" required />
            <flux:button type="submit" variant="primary" icon="archive-box-arrow-down">{{ __('Run import') }}</flux:button>
        </form>
    </div>
</x-layouts::app>
