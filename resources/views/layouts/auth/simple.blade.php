<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-app-surface text-app-copy antialiased">
        <div class="flex min-h-svh flex-col items-center justify-center gap-8 p-6 md:p-10">
            <div class="w-full max-w-md">
                <a href="{{ route('home') }}" class="mb-8 flex flex-col items-center gap-3 font-medium" wire:navigate>
                    <span class="flex size-12 items-center justify-center rounded-xl bg-brand-red text-sm font-black text-white shadow-[0_12px_30px_rgba(188,0,1,0.18)]">
                        MY
                    </span>
                    <span class="text-sm font-bold text-brand-navy dark:text-white">{{ config('app.name', 'Malaysia Holiday API') }}</span>
                </a>

                <div class="app-card p-8">
                    {{ $slot }}
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
