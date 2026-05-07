<x-layouts::app :title="__('Batch #:id', ['id' => $batch->id])">
    <div class="admin-page">
        <div class="admin-header">
            <div>
                <p class="app-label text-brand-red">{{ __('Batch Review') }}</p>
                <h1 class="app-page-title mt-2">{{ __('Batch #:id', ['id' => $batch->id]) }}</h1>
                <p class="app-page-copy mt-2">{{ $batch->status }} · {{ $batch->valid_rows }}/{{ $batch->total_rows }} {{ __('valid rows') }}</p>
            </div>
            <form method="POST" action="{{ route('admin.batches.publish', $batch) }}">
                @csrf
                <flux:button type="submit" variant="primary" icon="check-circle">{{ __('Publish') }}</flux:button>
            </form>
        </div>

        <div class="grid gap-4 md:grid-cols-4">
            <div class="admin-stat-card"><p class="app-label">{{ __('Total') }}</p><p class="mt-4 text-3xl font-bold text-brand-navy dark:text-white">{{ $batch->total_rows }}</p></div>
            <div class="admin-stat-card"><p class="app-label">{{ __('Valid') }}</p><p class="mt-4 text-3xl font-bold text-brand-navy dark:text-white">{{ $batch->valid_rows }}</p></div>
            <div class="admin-stat-card"><p class="app-label">{{ __('Invalid') }}</p><p class="mt-4 text-3xl font-bold text-brand-red">{{ $batch->invalid_rows }}</p></div>
            <div class="admin-stat-card"><p class="app-label">{{ __('Warnings') }}</p><p class="mt-4 text-3xl font-bold text-brand-gold">{{ $batch->warning_rows }}</p></div>
        </div>

        <div class="app-card overflow-hidden">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('State') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($batch->holidays as $holiday)
                        <tr>
                            <td class="font-mono">{{ $holiday->date->toDateString() }}</td>
                            <td><span class="app-badge app-badge-navy">{{ $holiday->state_code }}</span></td>
                            <td>{{ $holiday->name }}</td>
                            <td><span class="app-badge {{ $holiday->status === 'published' ? 'app-badge-gold' : 'app-badge-red' }}">{{ $holiday->status }}</span></td>
                            <td><a class="admin-action-link" href="{{ route('admin.holidays.edit', $holiday) }}">{{ __('Edit') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5">{{ __('No holidays in this batch.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts::app>
