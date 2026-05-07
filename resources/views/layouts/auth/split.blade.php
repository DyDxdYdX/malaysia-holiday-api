<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-app-surface text-app-copy antialiased">
        <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <div class="relative hidden h-full flex-col overflow-hidden bg-brand-navy p-10 text-white lg:flex">
                <div class="absolute inset-0 bg-linear-to-br from-brand-navy via-brand-navy to-brand-red/80"></div>
                <a href="{{ route('home') }}" class="relative z-20 flex items-center text-lg font-medium" wire:navigate>
                    <span class="me-3 flex size-10 items-center justify-center rounded-lg bg-white text-xs font-black text-white">
                        <img src="{{ asset('logo.png') }}" alt="{{ config('app.name', 'Malaysia Holiday API') }}" />
                    </span>
                    {{ config('app.name', 'Malaysia Holiday API') }}
                </a>

                @php
                $quotes = [
                    [
                        'message' => 'We read the government PDFs so your system does not have to.',
                        'author' => 'DyDxSoft',
                        'weight' => 40,
                    ],
                    [
                        'message' => 'Reliable holiday data starts with official sources, not assumptions.',
                        'author' => 'DyDxSoft',
                        'weight' => 30,
                    ],
                    [
                        'message' => 'Because public holiday data should not break your working-day logic.',
                        'author' => 'DyDxSoft',
                        'weight' => 15,
                    ],
                    [
                        'message' => 'Trust me bro, but bro is the OP.',
                        'author' => 'DyDxdYdX',
                        'weight' => 9,
                    ],
                    [
                        'message' => '67',
                        'author' => '67',
                        'weight' => 2,
                    ],
                    [
                        'message' => 'Either you keep refreshing to get here, or your luck is insane.',
                        'author' => 'Some unlucky guy',
                        'weight' => 2,
                    ],
                    [
                        'message' => 'Try to pull gacha if you see this.',
                        'author' => 'Silver Wolf',
                        'weight' => 2,
                    ],
                ];

                $totalWeight = collect($quotes)->sum('weight');
                $random = random_int(1, $totalWeight);

                $current = 0;

                foreach ($quotes as $quote) {
                    $current += $quote['weight'];

                    if ($random <= $current) {
                        $selectedQuote = $quote;
                        break;
                    }
                }

                $message = $selectedQuote['message'];
                $author = $selectedQuote['author'];
            @endphp

            <div class="relative z-20 mt-auto">
                <blockquote class="space-y-2">
                    <flux:heading size="lg" class="text-white">
                        &ldquo;{{ trim($message) }}&rdquo;
                    </flux:heading>

                    <footer class="space-y-1">
                        <flux:heading class="text-brand-gold-soft">
                            {{ trim($author) }}
                        </flux:heading>
                    </footer>
                </blockquote>
            </div>
            </div>
            <div class="w-full lg:p-8">
                <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                    <a href="{{ route('home') }}" class="z-20 flex flex-col items-center gap-2 font-medium lg:hidden" wire:navigate>
                        <span class="flex size-11 items-center justify-center rounded-xl bg-brand-red text-sm font-black text-white">
                            MY
                        </span>

                        <span class="sr-only">{{ config('app.name', 'Malaysia Holiday API') }}</span>
                    </a>
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
