<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="bg-app-surface">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
