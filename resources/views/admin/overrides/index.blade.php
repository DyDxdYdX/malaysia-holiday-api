<x-layouts::app :title="__('Holiday Overrides')">
    <div class="admin-page">
        <div class="admin-header">
            <div>
                <p class="app-label text-brand-red">{{ __('Published Corrections') }}</p>
                <h1 class="app-page-title mt-2">{{ __('Holiday Overrides') }}</h1>
                <p class="app-page-copy mt-2">{{ __('Apply traceable add, remove, replace, rename, and subject-to-change corrections.') }}</p>
            </div>
            <flux:button :href="route('admin.overrides.create')" variant="primary" icon="pencil-square" wire:navigate>{{ __('Create Override') }}</flux:button>
        </div>

        <div class="app-card overflow-hidden">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('State') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($overrides as $override)
                        <tr>
                            <td class="font-mono">{{ $override->date->toDateString() }}</td>
                            <td><span class="app-badge app-badge-navy">{{ $override->state_code }}</span></td>
                            <td>{{ $override->name }}</td>
                            <td><span class="app-badge app-badge-red">{{ $override->action }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4">{{ __('No overrides yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $overrides->links() }}
    </div>
</x-layouts::app>
