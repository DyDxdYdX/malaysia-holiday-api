<x-layouts::app :title="__('Batch #:id', ['id' => $batch->id])">
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold">{{ __('Batch #:id', ['id' => $batch->id]) }}</h1>
                <p class="text-sm text-neutral-500">{{ $batch->status }} · {{ $batch->valid_rows }}/{{ $batch->total_rows }} {{ __('valid rows') }}</p>
            </div>
            <form method="POST" action="{{ route('admin.batches.publish', $batch) }}">
                @csrf
                <flux:button type="submit" variant="primary">{{ __('Publish') }}</flux:button>
            </form>
        </div>

        <div class="overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-neutral-50 dark:bg-neutral-900">
                    <tr>
                        <th class="p-3">{{ __('Date') }}</th>
                        <th class="p-3">{{ __('State') }}</th>
                        <th class="p-3">{{ __('Name') }}</th>
                        <th class="p-3">{{ __('Status') }}</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($batch->holidays as $holiday)
                        <tr class="border-t border-neutral-200 dark:border-neutral-700">
                            <td class="p-3">{{ $holiday->date->toDateString() }}</td>
                            <td class="p-3">{{ $holiday->state_code }}</td>
                            <td class="p-3">{{ $holiday->name }}</td>
                            <td class="p-3">{{ $holiday->status }}</td>
                            <td class="p-3"><a class="underline" href="{{ route('admin.holidays.edit', $holiday) }}">{{ __('Edit') }}</a></td>
                        </tr>
                    @empty
                        <tr><td class="p-3" colspan="5">{{ __('No holidays in this batch.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts::app>
