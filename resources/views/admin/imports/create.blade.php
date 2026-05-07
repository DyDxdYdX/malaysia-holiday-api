<x-layouts::app :title="__('Import CSV')">
    <form class="max-w-2xl space-y-4" method="POST" action="{{ route('admin.sources.import.store', $source) }}" enctype="multipart/form-data">
        @csrf
        <h1 class="text-xl font-semibold">{{ __('Import CSV') }}</h1>
        <p class="text-sm text-neutral-500">{{ $source->source_name }} · {{ $source->year }}</p>

        <flux:input name="file" type="file" :label="__('CSV file')" required />
        <flux:button type="submit" variant="primary">{{ __('Run import') }}</flux:button>
    </form>
</x-layouts::app>
