<x-layouts::app :title="__('Dashboard')">
    <div class="admin-page">
        <div class="admin-header">
            <div>
                <p class="app-label text-brand-red">{{ __('Admin Console') }}</p>
                <h1 class="app-page-title mt-2">{{ __('Holiday Data Dashboard') }}</h1>
                <p class="app-page-copy mt-2">{{ __('Track sources, import batches, published holidays, and override activity from one operational view.') }}</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <flux:button :href="route('admin.sources.create')" variant="primary" icon="document-arrow-up" wire:navigate>{{ __('Upload Source') }}</flux:button>
                <flux:button :href="route('admin.batches.index')" variant="outline" icon="archive-box" wire:navigate>{{ __('Review Batches') }}</flux:button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="admin-stat-card">
                <p class="app-label">{{ __('Total Holidays') }}</p>
                <div class="mt-4 flex items-end justify-between">
                    <p class="text-3xl font-bold text-brand-navy dark:text-white">{{ number_format($holidayCount) }}</p>
                    <span class="app-badge app-badge-navy">{{ __('Catalog') }}</span>
                </div>
            </div>

            <div class="admin-stat-card">
                <p class="app-label">{{ __('Published') }}</p>
                <div class="mt-4 flex items-end justify-between">
                    <p class="text-3xl font-bold text-brand-navy dark:text-white">{{ number_format($publishedHolidayCount) }}</p>
                    <span class="app-badge app-badge-gold">{{ __('Public') }}</span>
                </div>
            </div>

            <div class="admin-stat-card">
                <p class="app-label">{{ __('Pending Batches') }}</p>
                <div class="mt-4 flex items-end justify-between">
                    <p class="text-3xl font-bold text-brand-navy dark:text-white">{{ number_format($pendingBatchCount) }}</p>
                    <span class="app-badge app-badge-red">{{ __('Review') }}</span>
                </div>
            </div>

            <div class="admin-stat-card">
                <p class="app-label">{{ __('Sources') }}</p>
                <div class="mt-4 flex items-end justify-between">
                    <p class="text-3xl font-bold text-brand-navy dark:text-white">{{ number_format($sourceCount) }}</p>
                    <span class="app-badge app-badge-navy">{{ number_format($overrideCount) }} {{ __('overrides') }}</span>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
            <section class="app-section">
                <div class="mb-5 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-brand-navy dark:text-white">{{ __('Recent Import Batches') }}</h2>
                        <p class="app-page-copy">{{ __('Latest import workflow activity awaiting review or publication.') }}</p>
                    </div>
                    <a href="{{ route('admin.batches.index') }}" class="admin-action-link">{{ __('View all') }}</a>
                </div>

                <div class="overflow-hidden rounded-lg border border-app-outline/70">
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
                            @forelse ($recentBatches as $batch)
                                <tr>
                                    <td><a class="admin-action-link" href="{{ route('admin.batches.show', $batch) }}">#{{ $batch->id }}</a></td>
                                    <td>{{ $batch->source?->source_name ?? __('Unknown') }}</td>
                                    <td><span class="app-badge app-badge-navy">{{ $batch->status }}</span></td>
                                    <td>{{ $batch->valid_rows }}/{{ $batch->total_rows }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">{{ __('No import batches yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="app-section bg-brand-navy text-white">
                <p class="app-label text-brand-gold-soft">{{ __('Workflow') }}</p>
                <h2 class="mt-3 text-2xl font-bold tracking-normal">{{ __('Source to published API data') }}</h2>
                <div class="mt-6 space-y-4 text-sm leading-6 text-slate-200">
                    <div class="rounded-lg border border-white/10 bg-white/5 p-4">
                        <p class="font-bold text-white">{{ __('1. Upload source') }}</p>
                        <p>{{ __('Store official source metadata and optional source file checksum.') }}</p>
                    </div>
                    <div class="rounded-lg border border-white/10 bg-white/5 p-4">
                        <p class="font-bold text-white">{{ __('2. Import and review') }}</p>
                        <p>{{ __('Parse CSV rows into draft holidays, then review batch records.') }}</p>
                    </div>
                    <div class="rounded-lg border border-white/10 bg-white/5 p-4">
                        <p class="font-bold text-white">{{ __('3. Publish or override') }}</p>
                        <p>{{ __('Publish confirmed holidays and apply traceable corrections when needed.') }}</p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-layouts::app>
