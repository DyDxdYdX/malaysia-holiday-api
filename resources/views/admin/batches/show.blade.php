<x-layouts::app :title="__('Batch #:id', ['id' => $batch->id])">
    <div class="admin-page">
        <div class="admin-header">
            <div>
                <p class="app-label text-brand-red">{{ __('Batch Review') }}</p>
                <h1 class="app-page-title mt-2">{{ __('Batch #:id', ['id' => $batch->id]) }}</h1>
                <p class="app-page-copy mt-2">{{ $batch->status }} · {{ $batch->import_method }} · {{ $batch->valid_rows }}/{{ $batch->total_rows }} {{ __('valid rows') }}</p>
                @if ($batch->provider || $batch->model)
                    <p class="app-page-copy mt-1">{{ $batch->provider }} · {{ $batch->model }}</p>
                @endif
            </div>
            <form method="POST" action="{{ route('admin.batches.publish', $batch) }}">
                @csrf
                <flux:button type="submit" variant="primary" icon="check-circle" :disabled="$batch->invalid_rows > 0 || $batch->status === 'draft'">{{ __('Publish') }}</flux:button>
            </form>
        </div>

        @if ($batch->failed_at)
            <div class="app-section border-brand-red/30">
                <p class="app-label text-brand-red">{{ __('Extraction failed') }}</p>
                <p class="mt-2 text-app-copy">{{ $batch->failure_reason }}</p>
            </div>
        @endif

        @if ($isPdfExtractionPending)
            <div class="app-section flex items-center gap-4 border-brand-gold/40">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-gold/10 text-brand-gold">
                    <flux:icon.loading class="size-5" />
                </div>
                <div>
                    <p class="font-semibold text-brand-navy dark:text-white">{{ __('PDF extraction in progress') }}</p>
                    <p class="app-page-copy mt-1">{{ __('Gemini is extracting holiday rows from the source PDF. This page will refresh automatically.') }}</p>
                </div>
            </div>
        @endif

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
                        <th>{{ __('Row') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('State') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Notes') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($batch->importRows as $row)
                        @php($payload = $row->normalized_payload ?? [])
                        <tr>
                            <td class="font-mono">{{ $row->row_number }}</td>
                            <td><span class="app-badge {{ $row->status === 'invalid' ? 'app-badge-red' : ($row->status === 'warning' ? 'app-badge-gold' : 'app-badge-navy') }}">{{ $row->status }}</span></td>
                            <td class="font-mono">{{ $payload['date'] ?? '—' }}</td>
                            <td>{{ $payload['state_codes'] ?? '—' }}</td>
                            <td>{{ $payload['name'] ?? '—' }}</td>
                            <td>
                                @foreach (($row->errors ?? []) as $error)
                                    <p class="text-brand-red">{{ $error }}</p>
                                @endforeach
                                @foreach (($row->warnings ?? []) as $warning)
                                    <p class="text-brand-gold">{{ $warning }}</p>
                                @endforeach
                                @if ($row->confidence !== null)
                                    <p class="font-mono text-xs text-app-muted">{{ __('Confidence') }}: {{ $row->confidence }}</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">{{ __('No row audit entries yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('admin.batches.approve-selected', $batch) }}" class="app-card overflow-hidden">
            @csrf
            <div class="flex items-center justify-between border-b border-app-border px-4 py-3">
                <p class="app-label">{{ __('Holiday approvals') }}</p>
                <flux:button type="submit" variant="primary" icon="check">{{ __('Approve Selected') }}</flux:button>
            </div>
            @error('holiday_ids')
                <p class="px-4 pt-3 text-sm text-brand-red">{{ $message }}</p>
            @enderror
            <table class="app-table">
                <thead>
                    <tr>
                        <th>{{ __('Select') }}</th>
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
                            <td>
                                @if ($holiday->status === 'draft')
                                    <input type="checkbox" name="holiday_ids[]" value="{{ $holiday->id }}" class="h-4 w-4 rounded border-app-border text-brand-navy focus:ring-brand-navy">
                                @endif
                            </td>
                            <td class="font-mono">{{ $holiday->date->toDateString() }}</td>
                            <td><span class="app-badge app-badge-navy">{{ implode(', ', $holiday->stateCodes()) }}</span></td>
                            <td>{{ $holiday->name }}</td>
                            <td><span class="app-badge {{ $holiday->status === 'published' ? 'app-badge-gold' : 'app-badge-red' }}">{{ $holiday->status }}</span></td>
                            <td><a class="admin-action-link" href="{{ route('admin.holidays.edit', $holiday) }}">{{ __('Edit') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6">{{ __('No holidays in this batch.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </form>
    </div>
</x-layouts::app>
