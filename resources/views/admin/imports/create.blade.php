<x-layouts::app :title="__('Import CSV')">
    <div class="admin-page">
        <div class="admin-header">
            <div>
                <p class="app-label text-brand-red">{{ __('CSV Import') }}</p>
                <h1 class="app-page-title mt-2">{{ __('Import CSV') }}</h1>
                <p class="app-page-copy mt-2">{{ $source->source_name }} · {{ $source->year }}</p>
            </div>
            <flux:button :href="route('admin.sources.import.template', $source)" icon="arrow-down-tray">{{ __('Download Template') }}</flux:button>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <form class="app-section space-y-5" method="POST" action="{{ route('admin.sources.import.store', $source) }}" enctype="multipart/form-data">
                @csrf

                <div>
                    <h2 class="text-lg font-semibold text-brand-navy dark:text-white">{{ __('CSV import') }}</h2>
                    <p class="app-page-copy mt-2">{{ __('Upload a completed template. Dates must use YYYY-MM-DD.') }}</p>
                </div>

                <flux:input name="file" type="file" :label="__('CSV file')" required />
                <flux:button type="submit" variant="primary" icon="archive-box-arrow-down">{{ __('Run CSV Import') }}</flux:button>
            </form>

            <form class="app-section space-y-5" method="POST" action="{{ route('admin.sources.import.pdf', $source) }}">
                @csrf

                <div>
                    <h2 class="text-lg font-semibold text-brand-navy dark:text-white">{{ __('PDF extraction') }}</h2>
                    <p class="app-page-copy mt-2">{{ __('Queue Gemini extraction from the stored source PDF, then review the draft rows before publishing.') }}</p>
                </div>

                <div class="rounded-lg border border-app-line p-4 text-sm text-app-copy dark:border-white/10">
                    <span class="app-label">{{ __('Source file') }}</span>
                    <p class="mt-2 break-all font-mono">{{ $source->file_path ?? __('No file uploaded') }}</p>
                </div>

                <flux:button type="submit" variant="primary" icon="sparkles" :disabled="! $source->file_path || strtolower(pathinfo($source->file_path, PATHINFO_EXTENSION)) !== 'pdf'">
                    {{ __('Extract PDF') }}
                </flux:button>
            </form>
        </div>
    </div>
</x-layouts::app>
