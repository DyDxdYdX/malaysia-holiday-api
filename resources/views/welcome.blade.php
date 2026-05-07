<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Malaysia Holiday API') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="app-shell antialiased">
        <header class="sticky top-0 z-40 border-b border-app-outline/70 bg-app-surface/95 backdrop-blur">
            <div class="app-container flex h-16 items-center justify-between gap-6">
                <a href="{{ route('home') }}" class="flex items-center gap-3 font-bold text-brand-navy dark:text-white">
                    <span class="flex size-9 items-center justify-center rounded-lg text-sm font-black text-white">
                        <img src="{{ asset('logo.png') }}" alt="{{ config('app.name', 'Malaysia Holiday API') }}" />
                    </span>
                    <span>{{ config('app.name', 'Malaysia Holiday API') }}</span>
                </a>

                <nav class="hidden items-center gap-8 text-sm font-semibold text-app-copy-muted md:flex">
                    <a href="#features" class="hover:text-brand-red">{{ __('Features') }}</a>
                    <a href="#api" class="hover:text-brand-red">{{ __('API Preview') }}</a>
                </nav>

                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-lg border border-brand-navy px-4 py-2 text-sm font-bold text-brand-navy dark:text-white hover:bg-brand-navy hover:text-white">{{ __('Dashboard') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="hidden rounded-lg border border-brand-navy px-4 py-2 text-sm font-bold text-brand-navy dark:text-white hover:bg-brand-navy hover:text-white sm:inline-flex">{{ __('Log in') }}</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="rounded-lg bg-brand-red px-4 py-2 text-sm font-bold text-white shadow-[0_8px_20px_rgba(188,0,1,0.18)] hover:bg-brand-red-bright">{{ __('Get started') }}</a>
                        @endif
                    @endauth
                </div>
            </div>
        </header>

        <main>
            <section class="relative overflow-hidden bg-brand-navy text-white">
                <div class="absolute inset-x-0 bottom-0 h-28 bg-linear-to-t from-app-surface to-transparent"></div>
                <div class="app-container relative grid min-h-[calc(100vh-4rem)] items-center gap-10 py-16 lg:grid-cols-[1.05fr_0.95fr] lg:py-24">
                    <div class="max-w-3xl">
                        <p class="app-label mb-5 text-brand-gold-soft">{{ __('Verified Malaysian holiday data') }}</p>
                        <h1 class="text-4xl font-bold leading-tight tracking-normal sm:text-5xl lg:text-6xl">
                            {{ __('Malaysia public holiday data, reviewed before it ships.') }}
                        </h1>
                        <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-200">
                            {{ __('A Laravel-backed API for state-level Malaysian public holidays, official source imports, admin review, publishing, and manual overrides.') }}
                        </p>
                        <div class="mt-8 flex flex-wrap gap-4">
                            <a href="#api" class="rounded-lg bg-brand-red px-6 py-3 text-sm font-bold text-white shadow-[0_12px_30px_rgba(188,0,1,0.25)] hover:bg-brand-red-bright">{{ __('View API preview') }}</a>
                            <a href="{{ route('login') }}" class="rounded-lg border border-slate-300 px-6 py-3 text-sm font-bold text-slate-100 hover:bg-white/10">{{ __('Open admin console') }}</a>
                        </div>
                    </div>

                    <div class="app-card border-white/10 bg-white/8 p-5 shadow-[0_18px_60px_rgba(0,0,0,0.24)] backdrop-blur">
                        <div class="mb-4 flex items-center justify-between border-b border-white/10 pb-4">
                            <div>
                                <p class="text-xs font-semibold tracking-wider text-slate-300 uppercase">{{ __('Endpoint') }}</p>
                                <p class="font-mono text-sm text-white">GET /api/v1/holidays</p>
                            </div>
                            <span class="app-badge bg-brand-gold-soft text-brand-navy">JSON</span>
                        </div>
                        <pre class="overflow-x-auto rounded-lg bg-app-code p-5 font-mono text-sm leading-6 text-slate-100"><code>{
  "year": 2026,
  "state_code": "SBH",
  "data": [
    {
      "name": "Pesta Kaamatan",
      "date": "2026-05-30",
      "scope": "state",
      "is_subject_to_change": false
    }
  ]
}</code></pre>
                    </div>
                </div>
            </section>

            <section class="border-y border-app-outline/70 bg-app-surface-card py-5">
                <div class="app-container grid gap-4 text-sm font-semibold text-app-copy-muted sm:grid-cols-3">
                    <div>{{ __('13 states + 3 federal territories') }}</div>
                    <div>{{ __('Draft review before publishing') }}</div>
                    <div>{{ __('Manual override audit trail') }}</div>
                </div>
            </section>

            <section id="features" class="py-16 sm:py-20">
                <div class="app-container">
                    <div class="max-w-2xl">
                        <p class="app-label text-brand-red">{{ __('Platform capabilities') }}</p>
                        <h2 class="mt-3 text-3xl font-bold tracking-normal text-brand-navy dark:text-white">{{ __('Built for operational holiday data.') }}</h2>
                        <p class="mt-4 app-page-copy">{{ __('The public API stays simple, while the admin workflow keeps source documents, imports, review, publishing, and overrides traceable.') }}</p>
                    </div>

                    <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ([
                            ['Official sources', 'Store PDFs, gazettes, state pages, and CSV source files with checksums.'],
                            ['CSV import workflow', 'Create review batches from source-linked CSV files before publishing.'],
                            ['Admin review', 'Edit, confirm, reject, and publish holidays through web-session protected screens.'],
                            ['State granularity', 'Represent federal, state, replacement, additional, and custom holiday types.'],
                            ['Manual overrides', 'Apply published corrections without rewriting import history.'],
                            ['Date check endpoint', 'Quickly answer whether a date is a holiday for a given state.'],
                        ] as [$title, $copy])
                            <article class="app-card p-6 transition hover:border-brand-red/60">
                                <div class="mb-5 flex size-11 items-center justify-center rounded-lg bg-app-surface-muted text-sm font-black text-brand-navy dark:text-white">{{ str($title)->substr(0, 2)->upper() }}</div>
                                <h3 class="text-lg font-bold text-brand-navy dark:text-white">{{ __($title) }}</h3>
                                <p class="mt-3 app-page-copy">{{ __($copy) }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="api" class="bg-app-surface-card py-16 sm:py-20">
                <div class="app-container grid gap-8 lg:grid-cols-[0.85fr_1.15fr]">
                    <div>
                        <p class="app-label text-brand-red">{{ __('Public API') }}</p>
                        <h2 class="mt-3 text-3xl font-bold tracking-normal text-brand-navy dark:text-white">{{ __('Stable JSON for apps and integrations.') }}</h2>
                        <p class="mt-4 app-page-copy">{{ __('Public consumers only need the JSON endpoints. Admin operations stay in the Livewire/Blade dashboard and never require bearer-token admin APIs.') }}</p>
                    </div>

                    <div class="app-code-block overflow-x-auto">
                        <pre><code>curl "{{ url('/api/v1/holidays/check?date=2026-05-30&state=SBH') }}"

{
  "date": "2026-05-30",
  "state_code": "SBH",
  "is_holiday": true,
  "holidays": [
    { "name": "Pesta Kaamatan", "scope": "state" }
  ]
}</code></pre>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
