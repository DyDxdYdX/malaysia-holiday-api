<x-layouts::app :title="__('Holiday Sources')">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">{{ __('Holiday Sources') }}</h1>
            <flux:button :href="route('admin.sources.create')" variant="primary">{{ __('Upload') }}</flux:button>
        </div>

        <div class="overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-neutral-50 dark:bg-neutral-900">
                    <tr>
                        <th class="p-3">{{ __('Year') }}</th>
                        <th class="p-3">{{ __('Source') }}</th>
                        <th class="p-3">{{ __('Type') }}</th>
                        <th class="p-3">{{ __('Status') }}</th>
                        <th class="p-3">{{ __('Batches') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sources as $source)
                        <tr class="border-t border-neutral-200 dark:border-neutral-700">
                            <td class="p-3">{{ $source->year }}</td>
                            <td class="p-3"><a class="underline" href="{{ route('admin.sources.show', $source) }}">{{ $source->source_name }}</a></td>
                            <td class="p-3">{{ $source->source_type }}</td>
                            <td class="p-3">{{ $source->status }}</td>
                            <td class="p-3">{{ $source->import_batches_count }}</td>
                        </tr>
                    @empty
                        <tr><td class="p-3" colspan="5">{{ __('No sources uploaded yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $sources->links() }}
    </div>
</x-layouts::app>
