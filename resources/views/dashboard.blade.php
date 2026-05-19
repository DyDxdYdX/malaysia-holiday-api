<x-layouts::app :title="__('Dashboard')">
    <div class="admin-page">
        <div class="admin-header">
            <div>
                <div class="flex items-center gap-2">
                    <div class="size-2 rounded-full bg-brand-red animate-pulse"></div>
                    <p class="app-label text-brand-red">{{ __('Operational Console') }}</p>
                </div>
                <h1 class="app-page-title mt-3">{{ __('Holiday Data Dashboard') }}</h1>
                <p class="app-page-copy mt-2">{{ __('Track official sources, manage import batches, and maintain the integrity of published holiday records.') }}</p>
            </div>

            <div class="flex flex-wrap gap-4">
                <flux:button :href="route('admin.sources.create')" variant="primary" icon="document-arrow-up" wire:navigate>{{ __('New Source') }}</flux:button>
                <flux:button :href="route('admin.batches.index')" variant="outline" icon="archive-box" wire:navigate>{{ __('All Batches') }}</flux:button>
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
            <div class="admin-stat-card group">
                <div class="absolute -right-4 -top-4 text-brand-navy/5 transition-transform group-hover:scale-110 group-hover:text-brand-navy/10 dark:text-white/5 dark:group-hover:text-white/10">
                    <flux:icon.calendar-days class="size-24" />
                </div>
                <div class="relative z-10">
                    <p class="app-label">{{ __('Total Holidays') }}</p>
                    <div class="mt-4 flex items-end justify-between">
                        <p class="text-4xl font-extrabold tracking-tight text-brand-navy dark:text-white">{{ number_format($holidayCount) }}</p>
                        <span class="app-badge app-badge-navy">{{ __('Catalog') }}</span>
                    </div>
                </div>
            </div>

            <div class="admin-stat-card group">
                <div class="absolute -right-4 -top-4 text-brand-gold/5 transition-transform group-hover:scale-110 group-hover:text-brand-gold/10">
                    <flux:icon.check-circle class="size-24" />
                </div>
                <div class="relative z-10">
                    <p class="app-label">{{ __('Published') }}</p>
                    <div class="mt-4 flex items-end justify-between">
                        <p class="text-4xl font-extrabold tracking-tight text-brand-navy dark:text-white">{{ number_format($publishedHolidayCount) }}</p>
                        <span class="app-badge app-badge-gold">{{ __('Public') }}</span>
                    </div>
                </div>
            </div>

            <div class="admin-stat-card group">
                <div class="absolute -right-4 -top-4 text-brand-red/5 transition-transform group-hover:scale-110 group-hover:text-brand-red/10">
                    <flux:icon.clock class="size-24" />
                </div>
                <div class="relative z-10">
                    <p class="app-label">{{ __('Pending Batches') }}</p>
                    <div class="mt-4 flex items-end justify-between">
                        <p class="text-4xl font-extrabold tracking-tight text-brand-navy dark:text-white">{{ number_format($pendingBatchCount) }}</p>
                        <span class="app-badge app-badge-red">{{ __('Review') }}</span>
                    </div>
                </div>
            </div>

            <div class="admin-stat-card group">
                <div class="absolute -right-4 -top-4 text-brand-navy/5 transition-transform group-hover:scale-110 group-hover:text-brand-navy/10 dark:text-white/5 dark:group-hover:text-white/10">
                    <flux:icon.document-duplicate class="size-24" />
                </div>
                <div class="relative z-10">
                    <p class="app-label">{{ __('Active Sources') }}</p>
                    <div class="mt-4 flex items-end justify-between">
                        <p class="text-4xl font-extrabold tracking-tight text-brand-navy dark:text-white">{{ number_format($sourceCount) }}</p>
                        <span class="app-badge app-badge-navy">{{ number_format($overrideCount) }} {{ __('overrides') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-8 lg:grid-cols-[1.2fr_0.8fr]">
            <section class="app-section">
                <div class="mb-8 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-extrabold tracking-tight text-brand-navy dark:text-white">{{ __('Recent Import Activity') }}</h2>
                        <p class="app-page-copy mt-1">{{ __('Latest batches awaiting verification or publication workflow steps.') }}</p>
                    </div>
                    <flux:button :href="route('admin.batches.index')" variant="ghost" size="sm" icon-trailing="arrow-right" wire:navigate>{{ __('View All') }}</flux:button>
                </div>

                <div class="overflow-hidden rounded-xl border border-app-outline bg-app-surface-low/30">
                    <table class="app-table">
                        <thead>
                            <tr>
                                <th>{{ __('Batch ID') }}</th>
                                <th>{{ __('Source Reference') }}</th>
                                <th>{{ __('Workflow Status') }}</th>
                                <th>{{ __('Record Progress') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentBatches as $batch)
                                <tr class="group cursor-pointer transition-colors" onclick="window.location='{{ route('admin.batches.show', $batch) }}'">
                                    <td><span class="font-mono font-bold text-brand-red">#{{ $batch->id }}</span></td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <flux:icon.document-text class="size-4 text-app-copy-muted" />
                                            <span class="font-semibold">{{ $batch->source?->source_name ?? __('Unknown Source') }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span @class([
                                            'app-badge',
                                            'app-badge-red' => in_array($batch->status, ['pending', 'failed']),
                                            'app-badge-gold' => $batch->status === 'reviewing',
                                            'app-badge-navy' => $batch->status === 'published',
                                        ])>{{ $batch->status }}</span>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="h-1.5 w-24 overflow-hidden rounded-full bg-app-outline">
                                                <div class="h-full bg-brand-navy dark:bg-brand-red" style="width: {{ ($batch->total_rows > 0) ? ($batch->valid_rows / $batch->total_rows) * 100 : 0 }}%"></div>
                                            </div>
                                            <span class="text-xs font-bold text-app-copy-muted">{{ $batch->valid_rows }}/{{ $batch->total_rows }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-12 text-center text-app-copy-muted">
                                        <flux:icon.archive-box-x-mark class="mx-auto mb-3 size-10 opacity-20" />
                                        <p class="font-medium">{{ __('No import batches found.') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="app-section bg-brand-navy text-white">
                <div class="mb-8">
                    <p class="app-label text-brand-gold-soft">{{ __('Operational Workflow') }}</p>
                    <h2 class="mt-3 text-2xl font-extrabold tracking-tight">{{ __('Source to Public API') }}</h2>
                    <p class="mt-2 text-sm leading-relaxed text-slate-400">{{ __('Our rigorous pipeline ensures data integrity across the platform.') }}</p>
                </div>

                <div class="space-y-6">
                    <div class="group relative flex gap-5 rounded-xl border border-white/10 bg-white/5 p-5 transition-all hover:bg-white/10">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-gold/20 text-brand-gold transition-transform group-hover:scale-110">
                            <flux:icon.document-plus class="size-5" />
                        </div>
                        <div>
                            <p class="font-bold text-white">{{ __('1. Upload Official Source') }}</p>
                            <p class="mt-1 text-xs leading-relaxed text-slate-400">{{ __('Archive PDFs or links to official state gazettes and government holiday announcements.') }}</p>
                        </div>
                    </div>

                    <div class="group relative flex gap-5 rounded-xl border border-white/10 bg-white/5 p-5 transition-all hover:bg-white/10">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-red/20 text-brand-red-bright transition-transform group-hover:scale-110">
                            <flux:icon.magnifying-glass-circle class="size-5" />
                        </div>
                        <div>
                            <p class="font-bold text-white">{{ __('2. Ingest and Verify') }}</p>
                            <p class="mt-1 text-xs leading-relaxed text-slate-400">{{ __('Parse CSV exports into draft batches. Cross-reference records against the uploaded source files.') }}</p>
                        </div>
                    </div>

                    <div class="group relative flex gap-5 rounded-xl border border-white/10 bg-white/5 p-5 transition-all hover:bg-white/10">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-500/20 text-emerald-400 transition-transform group-hover:scale-110">
                            <flux:icon.cloud-arrow-up class="size-5" />
                        </div>
                        <div>
                            <p class="font-bold text-white">{{ __('3. Publish with Confidence') }}</p>
                            <p class="mt-1 text-xs leading-relaxed text-slate-400">{{ __('Approved records are synced to the public API. Use overrides for gazetted mid-year corrections.') }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 rounded-xl bg-white/5 p-4 text-center">
                    <p class="text-[10px] font-bold tracking-widest text-slate-500 uppercase">{{ __('System Integrity Check') }}</p>
                    <div class="mt-2 flex items-center justify-center gap-2 text-emerald-400">
                        <span class="size-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="text-xs font-bold">{{ __('All systems operational') }}</span>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-layouts::app>
