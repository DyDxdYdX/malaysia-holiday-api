@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="{{ config('app.name', 'Malaysia Holiday API') }}" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-9 items-center justify-center rounded-lg bg-white text-xs font-black text-white shadow-sm">
            <img src="{{ asset('logo.png') }}" alt="{{ config('app.name', 'Malaysia Holiday API') }}" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="{{ config('app.name', 'Malaysia Holiday API') }}" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-9 items-center justify-center rounded-lg bg-white text-xs font-black text-white shadow-sm">
            <img src="{{ asset('logo.png') }}" alt="{{ config('app.name', 'Malaysia Holiday API') }}" />
        </x-slot>
    </flux:brand>
@endif
