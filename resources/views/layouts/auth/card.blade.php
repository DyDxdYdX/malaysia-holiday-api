<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-app-surface text-app-copy antialiased">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col gap-6">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="flex size-12 items-center justify-center rounded-xl bg-brand-red text-sm font-black text-white shadow-[0_12px_30px_rgba(188,0,1,0.18)]">
                        MY
                    </span>

                    <span class="text-sm font-bold text-brand-navy dark:text-white">{{ config('app.name', 'Malaysia Holiday API') }}</span>
                </a>

                <div class="flex flex-col gap-6">
                    <div class="app-card text-app-copy">
                        <div class="px-10 py-8">{{ $slot }}</div>
                    </div>
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
