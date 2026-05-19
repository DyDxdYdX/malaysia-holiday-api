@if ($isAdminView)
    <x-layouts::app :title="$title">
        @include('holidays.partials.calendar-content')
    </x-layouts::app>
@else
    <!DOCTYPE html>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
        <head>
            @include('partials.head')
        </head>
        <body class="app-shell antialiased">
            <header class="border-b border-app-outline bg-app-surface-card">
                <div class="app-container flex h-16 items-center justify-between gap-4">
                    <a href="{{ route('home') }}" class="text-sm font-bold tracking-wide text-brand-navy dark:text-white">{{ config('app.name', 'Malaysia Holiday API') }}</a>
                    <div class="flex items-center gap-2">
                        <flux:button :href="route('holidays.calendar')" variant="ghost" icon="calendar-days">{{ __('Calendar') }}</flux:button>
                        @auth
                            <flux:button :href="route('dashboard')" variant="primary" wire:navigate>{{ __('Dashboard') }}</flux:button>
                        @else
                            <flux:button :href="route('login')" variant="primary" wire:navigate>{{ __('Log in') }}</flux:button>
                        @endauth
                    </div>
                </div>
            </header>

            @include('holidays.partials.calendar-content')

            @fluxScripts
        </body>
    </html>
@endif
