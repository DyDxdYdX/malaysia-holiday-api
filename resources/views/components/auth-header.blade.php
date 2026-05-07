@props([
    'title',
    'description',
])

<div class="flex w-full flex-col gap-2 text-center">
    <flux:heading size="xl" class="text-brand-navy dark:text-white">{{ $title }}</flux:heading>
    <flux:subheading class="leading-6 text-app-copy-muted">{{ $description }}</flux:subheading>
</div>
