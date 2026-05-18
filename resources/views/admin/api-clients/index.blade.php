<x-layouts::app :title="__('API Clients')">
    <div class="admin-page">
        <div class="admin-header">
            <div>
                <p class="app-label text-brand-red">{{ __('API Security') }}</p>
                <h1 class="app-page-title mt-2">{{ __('API Clients') }}</h1>
                <p class="app-page-copy mt-2">{{ __('Manage API keys and per-client rate limits for private holiday endpoints.') }}</p>
            </div>
        </div>

        <form class="app-section max-w-2xl space-y-4" method="POST" action="{{ route('admin.api-clients.store') }}">
            @csrf
            <flux:input name="name" :label="__('Client name')" :value="old('name')" required />
            <flux:input name="rate_limit_per_minute" type="number" min="1" :label="__('Rate limit per minute')" :value="old('rate_limit_per_minute', 60)" required />
            <flux:select name="status" :label="__('Status')" required>
                @foreach (['active', 'disabled'] as $status)
                    <flux:select.option value="{{ $status }}" :selected="old('status', 'active') === $status">{{ ucfirst($status) }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:button type="submit" variant="primary" icon="key">{{ __('Create API Client') }}</flux:button>
        </form>

        <div class="app-card overflow-hidden">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Rate Limit') }}</th>
                        <th>{{ __('Created') }}</th>
                        <th>{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($apiClients as $client)
                        <tr>
                            <td>{{ $client->name }}</td>
                            <td><span class="app-badge {{ $client->status === 'active' ? 'app-badge-gold' : 'app-badge-red' }}">{{ $client->status }}</span></td>
                            <td>{{ $client->rate_limit_per_minute }}/min</td>
                            <td class="font-mono">{{ $client->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td>
                                @if ($client->status === 'active')
                                    <form method="POST" action="{{ route('admin.api-clients.disable', $client) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="admin-action-link text-brand-red">{{ __('Disable') }}</button>
                                    </form>
                                @else
                                    <span class="text-sm text-app-copy/70">{{ __('Disabled') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">{{ __('No API clients yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $apiClients->links() }}
    </div>
</x-layouts::app>
