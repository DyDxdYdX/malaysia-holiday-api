<x-layouts::app :title="__('Holiday Sources')">
    <div class="admin-page">
        <div class="admin-header">
            <div>
                <p class="app-label text-brand-red">{{ __('Data Operations') }}</p>
                <h1 class="app-page-title mt-2">{{ __('Import Sources') }}</h1>
                <p class="app-page-copy mt-2">{{ __('Manage official source documents used for holiday imports.') }}</p>
            </div>
            <flux:button :href="route('admin.sources.create')" variant="primary" icon="document-arrow-up" wire:navigate>{{ __('Upload Source') }}</flux:button>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="admin-stat-card">
                <p class="app-label">{{ __('Total Sources') }}</p>
                <p class="mt-4 text-3xl font-bold text-brand-navy dark:text-white">{{ number_format($sources->total()) }}</p>
            </div>
            <div class="admin-stat-card">
                <p class="app-label">{{ __('Visible Page') }}</p>
                <p class="mt-4 text-3xl font-bold text-brand-navy dark:text-white">{{ number_format($sources->count()) }}</p>
            </div>
            <div class="admin-stat-card">
                <p class="app-label">{{ __('Workflow') }}</p>
                <p class="mt-4 app-page-copy">{{ __('Upload, import, review, then publish.') }}</p>
            </div>
        </div>

        <div class="app-card overflow-hidden">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>{{ __('Year') }}</th>
                        <th>{{ __('Source') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Batches') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sources as $source)
                        <tr>
                            <td>{{ $source->year }}</td>
                            <td><a class="admin-action-link" href="{{ route('admin.sources.show', $source) }}">{{ $source->source_name }}</a></td>
                            <td><span class="app-badge app-badge-navy">{{ $source->source_type }}</span></td>
                            <td><span class="app-badge {{ $source->status === 'active' ? 'app-badge-gold' : 'app-badge-red' }}">{{ $source->status }}</span></td>
                            <td>{{ $source->import_batches_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">{{ __('No sources uploaded yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $sources->links() }}
    </div>
</x-layouts::app>
