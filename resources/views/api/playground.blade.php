<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        @include('partials.head', [
            'title' => 'API Playground',
            'description' => 'Test Malaysia Holiday API endpoints directly in your browser with interactive request parameters and live JSON responses.',
            'canonical' => route('api.playground'),
            'ogType' => 'website',
        ])
    </head>
    <body class="app-shell antialiased">
        {{-- Header --}}
        <header class="sticky top-0 z-40 border-b border-app-outline bg-app-surface/90 backdrop-blur-lg">
            <div class="app-container flex h-16 items-center justify-between gap-4">
                <a href="{{ route('home') }}" class="flex items-center gap-3 font-extrabold tracking-tight text-brand-navy dark:text-white">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-white shadow-lg">
                        <img src="{{ asset('logo.png') }}" class="size-6" alt="{{ config('app.name') }}" />
                    </span>
                    <span class="text-xl">{{ config('app.name', 'Holiday API') }}</span>
                </a>
                <nav class="flex items-center gap-5 text-sm font-semibold text-app-copy-muted">
                    <a href="{{ route('api.docs') }}" class="hidden transition-colors hover:text-brand-red sm:inline">{{ __('Docs') }}</a>
                    <span class="hidden font-bold text-brand-navy dark:text-white sm:inline">{{ __('Playground') }}</span>
                    <a href="{{ route('home') }}" class="transition-colors hover:text-brand-red">{{ __('Home') }}</a>
                </nav>
            </div>
        </header>

        <main>
            {{-- Hero --}}
            <section class="border-b border-app-outline bg-app-surface py-10 sm:py-14">
                <div class="app-container">
                    <p class="app-label text-brand-red">{{ __('API Playground') }}</p>
                    <h1 class="mt-3 text-4xl font-extrabold tracking-tight text-brand-navy dark:text-white sm:text-5xl">
                        {{ __('Try the API live.') }}
                    </h1>
                    <p class="mt-4 max-w-2xl text-lg leading-relaxed text-app-copy-muted">
                        {{ __('Select an endpoint, fill in the parameters, and hit Send to get a real response. No API key or setup required.') }}
                    </p>
                </div>
            </section>

            {{-- Playground --}}
            <section class="py-10 sm:py-16">
                <div class="app-container">
                    @livewire('api-playground')
                </div>
            </section>
        </main>

        <footer class="border-t border-app-outline bg-app-surface py-10 dark:bg-brand-navy">
            <div class="app-container flex flex-col items-center justify-between gap-4 sm:flex-row">
                <p class="text-sm text-app-copy-muted">
                    &copy; {{ date('Y') }} {{ config('app.name', 'Malaysia Holiday API') }}. {{ __('All rights reserved.') }}
                </p>
                <div class="flex gap-6 text-sm font-semibold text-app-copy-muted">
                    <a href="{{ route('api.docs') }}" class="hover:text-brand-red">{{ __('API Docs') }}</a>
                    <a href="https://dydxsoft.my" target="_blank" rel="noopener noreferrer" class="hover:text-brand-red">{{ __('DyDxSoft') }}</a>
                </div>
            </div>
        </footer>
    </body>
</html>
