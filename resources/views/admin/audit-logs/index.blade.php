<x-layouts::app :title="__('Audit Logs')">
    <div class="admin-page">
        <div class="admin-header">
            <div>
                <p class="app-label text-brand-red">{{ __('Traceability') }}</p>
                <h1 class="app-page-title mt-2">{{ __('Audit Logs') }}</h1>
                <p class="app-page-copy mt-2">{{ __('Review create, update, and delete actions with actor and before/after details.') }}</p>
            </div>
        </div>

        <div class="app-card overflow-hidden">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>{{ __('Timestamp') }}</th>
                        <th>{{ __('Action') }}</th>
                        <th>{{ __('Entity') }}</th>
                        <th>{{ __('Entity ID') }}</th>
                        <th>{{ __('Actor') }}</th>
                        <th>{{ __('Old Values') }}</th>
                        <th>{{ __('New Values') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($auditLogs as $log)
                        <tr>
                            <td class="font-mono">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td><span class="app-badge app-badge-red">{{ $log->action }}</span></td>
                            <td>{{ $log->entity_type }}</td>
                            <td>{{ $log->entity_id ?? '—' }}</td>
                            <td>{{ $log->actor?->name ?? __('System') }}</td>
                            <td>
                                @if ($log->old_values !== null)
                                    <pre class="max-w-64 overflow-x-auto text-xs">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($log->new_values !== null)
                                    <pre class="max-w-64 overflow-x-auto text-xs">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">{{ __('No audit entries yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $auditLogs->links() }}
    </div>
</x-layouts::app>
