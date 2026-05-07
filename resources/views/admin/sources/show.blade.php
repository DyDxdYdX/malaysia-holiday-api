<x-layouts::app :title="$source->source_name">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold">{{ $source->source_name }}</h1>
                <p class="text-sm text-neutral-500">{{ $source->year }} · {{ $source->source_type }} · {{ $source->status }}</p>
            </div>
            <flux:button :href="route('admin.sources.import.create', $source)" variant="primary">{{ __('Import CSV') }}</flux:button>
        </div>

        <dl class="grid gap-4 text-sm md:grid-cols-2">
            <div><dt class="font-medium">{{ __('Checksum') }}</dt><dd class="break-all">{{ $source->checksum ?? __('None') }}</dd></div>
            <div><dt class="font-medium">{{ __('Uploaded by') }}</dt><dd>{{ $source->uploader?->name ?? __('Unknown') }}</dd></div>
            <div><dt class="font-medium">{{ __('Source URL') }}</dt><dd>{{ $source->source_url ?? __('None') }}</dd></div>
            <div><dt class="font-medium">{{ __('File path') }}</dt><dd>{{ $source->file_path ?? __('None') }}</dd></div>
        </dl>

        <div class="space-y-2">
            <h2 class="font-semibold">{{ __('Import Batches') }}</h2>
            @forelse ($source->importBatches as $batch)
                <a class="block underline" href="{{ route('admin.batches.show', $batch) }}">#{{ $batch->id }} · {{ $batch->status }} · {{ $batch->total_rows }} {{ __('rows') }}</a>
            @empty
                <p class="text-sm text-neutral-500">{{ __('No import batches yet.') }}</p>
            @endforelse
        </div>
    </div>
</x-layouts::app>
