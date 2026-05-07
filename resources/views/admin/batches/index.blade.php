<x-layouts::app :title="__('Import Batches')">
    <div class="admin-page">
        <div class="admin-header">
            <div>
                <p class="app-label text-brand-red">{{ __('Review Workflow') }}</p>
                <h1 class="app-page-title mt-2">{{ __('Import Batches') }}</h1>
                <p class="app-page-copy mt-2">{{ __('Review imported draft holidays before publishing them to the public API.') }}</p>
            </div>
        </div>

        <div class="app-card overflow-hidden">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>{{ __('Batch') }}</th>
                        <th>{{ __('Source') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Rows') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($batches as $batch)
                        <tr>
                            <td><a class="admin-action-link" href="{{ route('admin.batches.show', $batch) }}">#{{ $batch->id }}</a></td>
                            <td>{{ $batch->source?->source_name }}</td>
                            <td><span class="app-badge {{ $batch->status === 'published' ? 'app-badge-gold' : 'app-badge-red' }}">{{ $batch->status }}</span></td>
                            <td>{{ $batch->valid_rows }}/{{ $batch->total_rows }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">{{ __('No batches yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $batches->links() }}
    </div>
</x-layouts::app>
