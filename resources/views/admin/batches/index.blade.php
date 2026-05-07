<x-layouts::app :title="__('Import Batches')">
    <div class="space-y-6">
        <h1 class="text-xl font-semibold">{{ __('Import Batches') }}</h1>

        <div class="overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-neutral-50 dark:bg-neutral-900">
                    <tr>
                        <th class="p-3">{{ __('Batch') }}</th>
                        <th class="p-3">{{ __('Source') }}</th>
                        <th class="p-3">{{ __('Status') }}</th>
                        <th class="p-3">{{ __('Rows') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($batches as $batch)
                        <tr class="border-t border-neutral-200 dark:border-neutral-700">
                            <td class="p-3"><a class="underline" href="{{ route('admin.batches.show', $batch) }}">#{{ $batch->id }}</a></td>
                            <td class="p-3">{{ $batch->source?->source_name }}</td>
                            <td class="p-3">{{ $batch->status }}</td>
                            <td class="p-3">{{ $batch->valid_rows }}/{{ $batch->total_rows }}</td>
                        </tr>
                    @empty
                        <tr><td class="p-3" colspan="4">{{ __('No batches yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $batches->links() }}
    </div>
</x-layouts::app>
