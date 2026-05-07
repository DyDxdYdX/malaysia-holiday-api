<x-layouts::app :title="$source->source_name">
    <div class="admin-page">
        <div class="admin-header">
            <div>
                <p class="app-label text-brand-red">{{ __('Source Detail') }}</p>
                <h1 class="app-page-title mt-2">{{ $source->source_name }}</h1>
                <p class="app-page-copy mt-2">{{ $source->year }} · {{ $source->source_type }} · {{ $source->status }}</p>
            </div>
            <flux:button :href="route('admin.sources.import.create', $source)" variant="primary" icon="archive-box-arrow-down" wire:navigate>{{ __('Import CSV') }}</flux:button>
        </div>

        <dl class="app-section grid gap-5 text-sm md:grid-cols-2">
            <div><dt class="app-label">{{ __('Checksum') }}</dt><dd class="mt-2 break-all font-mono text-app-copy">{{ $source->checksum ?? __('None') }}</dd></div>
            <div><dt class="app-label">{{ __('Uploaded by') }}</dt><dd class="mt-2">{{ $source->uploader?->name ?? __('Unknown') }}</dd></div>
            <div><dt class="app-label">{{ __('Source URL') }}</dt><dd class="mt-2 break-all">{{ $source->source_url ?? __('None') }}</dd></div>
            <div><dt class="app-label">{{ __('File path') }}</dt><dd class="mt-2 break-all font-mono">{{ $source->file_path ?? __('None') }}</dd></div>
        </dl>

        <section class="app-section">
            <h2 class="text-lg font-bold text-brand-navy dark:text-white">{{ __('Import Batches') }}</h2>
            <div class="mt-4 space-y-3">
                @forelse ($source->importBatches as $batch)
                    <a class="flex items-center justify-between rounded-lg border border-app-outline/70 bg-app-surface-low px-4 py-3 hover:border-brand-red" href="{{ route('admin.batches.show', $batch) }}">
                        <span class="font-semibold text-brand-navy dark:text-white">#{{ $batch->id }}</span>
                        <span class="app-badge app-badge-navy">{{ $batch->status }}</span>
                        <span class="text-sm text-app-copy-muted">{{ $batch->total_rows }} {{ __('rows') }}</span>
                    </a>
                @empty
                    <p class="app-page-copy">{{ __('No import batches yet.') }}</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts::app>
