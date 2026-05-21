@if ($isAdminView)
    <x-layouts::app :title="$title">
        @include('holidays.partials.calendar-content')
    </x-layouts::app>
@else
    <!DOCTYPE html>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
        <head>
            @include('partials.head', [
                'title' => $title,
                'description' => $subtitle ?? 'Browse published holidays in Malaysia.',
                'canonical' => route('holidays.calendar'),
                'ogType' => 'website',
            ])
        </head>
        <body class="app-shell antialiased">
            {{-- Premium Header matching home & playground --}}
            <header class="sticky top-0 z-40 border-b border-app-outline bg-app-surface/80 backdrop-blur-lg">
                <div class="app-container flex h-16 items-center justify-between gap-6">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 font-extrabold tracking-tight text-brand-navy dark:text-white">
                        <span class="flex size-9 items-center justify-center rounded-xl bg-white shadow-lg">
                            <img src="{{ asset('logo.png') }}" class="size-6" alt="{{ config('app.name', 'Malaysia Holiday API') }}" />
                        </span>
                        <span class="text-xl">{{ config('app.name', 'Holiday API') }}</span>
                    </a>

                    <nav class="hidden items-center gap-10 text-sm font-bold text-app-copy-muted md:flex">
                        <a href="{{ route('home') }}#features" class="transition-colors hover:text-brand-red">{{ __('Features') }}</a>
                        <a href="{{ route('home') }}#workflow" class="transition-colors hover:text-brand-red">{{ __('Workflow') }}</a>
                        <span class="text-brand-red font-bold">{{ __('Calendar') }}</span>
                        <a href="{{ route('api.playground') }}" class="transition-colors hover:text-brand-red">{{ __('Playground') }}</a>
                    </nav>

                    <div class="flex items-center gap-4">
                        @auth
                            <flux:button :href="route('dashboard')" variant="primary" wire:navigate>{{ __('Dashboard') }}</flux:button>
                        @else
                            <flux:button :href="route('login')" variant="ghost" class="hidden sm:inline-flex" wire:navigate>{{ __('Log in') }}</flux:button>
                            <flux:button :href="route('api.docs')" variant="primary">{{ __('API Docs') }}</flux:button>
                        @endauth
                    </div>
                </div>
            </header>

            {{-- Main Layout Container --}}
            <main class="app-container py-8 lg:py-12">
                @include('holidays.partials.calendar-content')
            </main>

            {{-- Footer matching home & playground --}}
            <footer class="border-t border-app-outline bg-app-surface py-12 dark:bg-brand-navy">
                <div class="app-container flex flex-col items-center justify-between gap-4 sm:flex-row">
                    <p class="text-sm text-app-copy-muted">
                        &copy; {{ date('Y') }} {{ config('app.name', 'Malaysia Holiday API') }}. {{ __('All rights reserved.') }}
                    </p>
                    <div class="flex gap-6 text-sm font-semibold text-app-copy-muted">
                        <a href="https://dydxsoft.my" target="_blank" rel="noopener noreferrer" class="hover:text-brand-red">{{ __('DyDxSoft') }}</a>
                        <a href="https://github.com/DyDxdYdX" target="_blank" rel="noopener noreferrer" class="hover:text-brand-red">{{ __('GitHub') }}</a>
                    </div>
                </div>
            </footer>

            @persist('toast')
                <flux:toast.group>
                    <flux:toast />
                </flux:toast.group>
            @endpersist

            @fluxScripts
        </body>
    </html>
@endif
