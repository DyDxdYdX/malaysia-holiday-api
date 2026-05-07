<x-layouts::app :title="__('Holiday Overrides')">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">{{ __('Holiday Overrides') }}</h1>
            <flux:button :href="route('admin.overrides.create')" variant="primary">{{ __('Create') }}</flux:button>
        </div>

        <div class="overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-neutral-50 dark:bg-neutral-900">
                    <tr>
                        <th class="p-3">{{ __('Date') }}</th>
                        <th class="p-3">{{ __('State') }}</th>
                        <th class="p-3">{{ __('Name') }}</th>
                        <th class="p-3">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($overrides as $override)
                        <tr class="border-t border-neutral-200 dark:border-neutral-700">
                            <td class="p-3">{{ $override->date->toDateString() }}</td>
                            <td class="p-3">{{ $override->state_code }}</td>
                            <td class="p-3">{{ $override->name }}</td>
                            <td class="p-3">{{ $override->action }}</td>
                        </tr>
                    @empty
                        <tr><td class="p-3" colspan="4">{{ __('No overrides yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $overrides->links() }}
    </div>
</x-layouts::app>
