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
        <header class="sticky top-0 z-40 border-b border-app-outline bg-app-surface/80 backdrop-blur-lg">
            <div class="app-container flex h-16 items-center justify-between gap-6">
                <a href="{{ route('home') }}" class="flex items-center gap-3 font-extrabold tracking-tight text-brand-navy dark:text-white">
                    <span class="flex size-9 items-center justify-center rounded-xl bg-white shadow-lg dark:bg-brand-red">
                        <img src="{{ asset('logo.png') }}" class="size-6" alt="{{ config('app.name', 'Malaysia Holiday API') }}" />
                    </span>
                    <span class="text-xl">{{ config('app.name', 'Holiday API') }}</span>
                </a>

                <nav class="hidden items-center gap-10 text-sm font-bold text-app-copy-muted md:flex">
                    <a href="#features" class="transition-colors hover:text-brand-red">{{ __('Features') }}</a>
                    <a href="#workflow" class="transition-colors hover:text-brand-red">{{ __('Workflow') }}</a>
                    <a href="{{ route('api.playground') }}" class="transition-colors hover:text-brand-red">{{ __('Playground') }}</a>
                    <a href="{{ route('api.docs') }}" class="transition-colors hover:text-brand-red">{{ __('API Docs') }}</a>
                </nav>

                <div class="flex items-center gap-4">
                    @auth
                        <flux:button :href="route('dashboard')" variant="primary" wire:navigate>{{ __('Go to Dashboard') }}</flux:button>
                    @else
                        <flux:button :href="route('login')" variant="ghost" class="hidden sm:inline-flex" wire:navigate>{{ __('Log in') }}</flux:button>
                        <flux:button :href="route('api.docs')" variant="primary">{{ __('API Docs') }}</flux:button>
                    @endauth
                </div>
            </div>
        </header>

        <main>
            <section class="relative overflow-hidden bg-brand-navy py-20 text-white lg:py-32">
                <!-- Mesh Gradient Background -->
                <div class="absolute inset-0 z-0 opacity-30">
                    <div class="absolute -top-[10%] -left-[10%] size-[50%] rounded-full bg-brand-red blur-[120px]"></div>
                    <div class="absolute top-[20%] -right-[10%] size-[40%] rounded-full bg-brand-gold blur-[120px]"></div>
                    <div class="absolute -bottom-[20%] left-[20%] size-[60%] rounded-full bg-slate-800 blur-[120px]"></div>
                </div>

                <div class="app-container relative z-10 grid items-center gap-16 lg:grid-cols-2">
                    <div class="max-w-3xl">
                        <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-[11px] font-bold tracking-widest text-brand-gold-soft uppercase backdrop-blur-sm">
                            <span class="relative flex size-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-brand-gold opacity-75"></span>
                                <span class="relative inline-flex size-2 rounded-full bg-brand-gold"></span>
                            </span>
                            {{ __('Verified Malaysian Holiday Data') }}
                        </div>
                        <h1 class="mt-8 text-5xl font-extrabold leading-[1.1] tracking-tight sm:text-6xl xl:text-7xl">
                            {{ __('The Source of Truth for') }} <span class="text-brand-gold">{{ __('Malaysian') }}</span> {{ __('Holidays.') }}
                        </h1>
                        <p class="mt-8 max-w-xl text-lg leading-relaxed text-slate-300">
                            {{ __('A high-integrity API for state-level holiday data. Powered by official source imports, rigorous admin review, and transparent audit trails.') }}
                        </p>
                        <div class="mt-12 flex flex-wrap gap-5">
                            <flux:button :href="url('/api/v1/holidays?year=2026')" variant="primary" class="!px-8 !py-4 text-base">{{ __('Try the API') }}</flux:button>
                            <flux:button :href="route('api.docs')" variant="primary" class="!bg-white/5 !px-8 !py-4 text-base text-white hover:!bg-white/10">{{ __('Read Documentation') }}</flux:button>
                        </div>
                    </div>

                    <div class="relative">
                        <div class="absolute -inset-4 rounded-[2rem] bg-brand-gold/10 blur-2xl"></div>
                        <div class="relative overflow-hidden rounded-2xl border border-white/10 bg-app-code shadow-2xl">
                            <div class="flex items-center justify-between border-b border-white/5 bg-white/5 px-4 py-3">
                                <div class="flex gap-1.5">
                                    <div class="size-3 rounded-full bg-red-500/50"></div>
                                    <div class="size-3 rounded-full bg-amber-500/50"></div>
                                    <div class="size-3 rounded-full bg-emerald-500/50"></div>
                                </div>
                                <div class="text-[11px] font-bold tracking-widest text-slate-500 uppercase">GET /api/v1/holidays</div>
                                <div class="size-4"></div>
                            </div>
                            <div class="p-6">
                                <pre class="font-mono text-sm leading-relaxed text-slate-300"><code>{
  <span class="text-brand-gold">"year"</span>: 2026,
  <span class="text-brand-gold">"state_code"</span>: "SBH",
  <span class="text-brand-gold">"data"</span>: [
    {
      <span class="text-brand-red-bright">"id"</span>: 1,
      <span class="text-brand-red-bright">"name"</span>: "Pesta Kaamatan",
      <span class="text-brand-red-bright">"date"</span>: "2026-05-30",
      <span class="text-brand-red-bright">"scope"</span>: "state",
      <span class="text-brand-red-bright">"state_codes"</span>: ["SBH"],
      <span class="text-brand-red-bright">"is_subject_to_change"</span>: false
    }
  ]
}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="border-y border-app-outline bg-white py-10 dark:bg-brand-navy-muted">
                <div class="app-container">
                    <div class="flex flex-wrap items-center justify-center gap-x-12 gap-y-8 text-sm font-bold text-app-copy-muted opacity-60 grayscale transition-all hover:opacity-100 hover:grayscale-0">
                        <div class="flex items-center gap-2"><flux:icon.globe-asia-australia class="size-5" /> {{ __('13 States + 3 Federal Territories') }}</div>
                        <div class="flex items-center gap-2"><flux:icon.shield-check class="size-5" /> {{ __('Multi-Stage Review Workflow') }}</div>
                        <div class="flex items-center gap-2"><flux:icon.clock class="size-5" /> {{ __('Real-Time Manual Overrides') }}</div>
                        <div class="flex items-center gap-2"><flux:icon.document-text class="size-5" /> {{ __('PDF & Gazette Source Tracking') }}</div>
                    </div>
                </div>
            </section>

            <section id="features" class="py-24 sm:py-32">
                <div class="app-container">
                    <div class="text-center">
                        <p class="app-label text-brand-red">{{ __('Platform Capabilities') }}</p>
                        <h2 class="mt-4 text-4xl font-extrabold tracking-tight text-brand-navy dark:text-white sm:text-5xl">
                            {{ __('Built for enterprise-grade operations.') }}
                        </h2>
                        <p class="mt-6 mx-auto max-w-2xl text-lg leading-relaxed text-app-copy-muted">
                            {{ __('We provide the tools to ingest, verify, and serve Malaysian holiday data with absolute confidence. No more scraping unreliable sources.') }}
                        </p>
                    </div>

                    <div class="mt-20 grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ([
                            ['Official sources', 'archive-box', 'Store PDFs, gazettes, and official state calendars with cryptographic checksums.'],
                            ['Import workflow', 'document-arrow-up', 'Seamless CSV ingestion with automated validation and batch-level review.'],
                            ['Admin review', 'shield-check', 'Expert review interface to confirm or reject holidays before they hit production.'],
                            ['State granularity', 'map', 'Full support for federal, state, and regional holidays with precise geographical scoping.'],
                            ['Manual overrides', 'pencil-square', 'Apply emergency corrections and gazetted changes without affecting historical imports.'],
                            ['Date check API', 'calendar-days', 'Highly optimized endpoints to verify holiday status for any date and state combination.'],
                        ] as [$title, $icon, $copy])
                            <article class="group app-card p-8">
                                <div class="mb-6 flex size-12 items-center justify-center rounded-xl bg-brand-navy text-white transition-transform group-hover:scale-110 dark:bg-brand-red">
                                    <flux:icon :name="$icon" class="size-6" />
                                </div>
                                <h3 class="text-xl font-bold text-brand-navy dark:text-white">{{ __($title) }}</h3>
                                <p class="mt-4 app-page-copy">{{ __($copy) }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="workflow" class="relative overflow-hidden bg-brand-navy py-24 text-white sm:py-32">
                <div class="app-container relative z-10">
                    <div class="grid gap-16 lg:grid-cols-2 lg:items-center">
                        <div>
                            <p class="app-label text-brand-gold">{{ __('The Workflow') }}</p>
                            <h2 class="mt-4 text-4xl font-extrabold tracking-tight sm:text-5xl">
                                {{ __('From PDF to API in') }} <span class="italic text-brand-gold">{{ __('minutes') }}</span>.
                            </h2>
                            <p class="mt-6 text-lg leading-relaxed text-slate-300">
                                {{ __('Our multi-stage pipeline ensures that every single holiday record is backed by an official document and verified by a human administrator.') }}
                            </p>

                            <div class="mt-12 space-y-8">
                                <div class="flex gap-6">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/10 font-bold text-brand-gold">1</div>
                                    <div>
                                        <h4 class="text-lg font-bold">{{ __('Source Ingestion') }}</h4>
                                        <p class="mt-1 text-slate-400">{{ __('Upload official gazettes or state announcements. Each source is tracked and archived.') }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-6">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/10 font-bold text-brand-gold">2</div>
                                    <div>
                                        <h4 class="text-lg font-bold">{{ __('Batch Review') }}</h4>
                                        <p class="mt-1 text-slate-400">{{ __('Imports are held in draft batches. Admins cross-reference records against the original source.') }}</p>
                                    </div>
                                </div>
                                <div class="flex gap-6">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/10 font-bold text-brand-gold">3</div>
                                    <div>
                                        <h4 class="text-lg font-bold">{{ __('Global Publication') }}</h4>
                                        <p class="mt-1 text-slate-400">{{ __('Approved data is instantly available via our global API nodes with high-availability.') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-4 pt-12">
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm">
                                    <flux:icon.document-text class="mb-4 size-8 text-brand-gold" />
                                    <div class="text-2xl font-bold">PDF</div>
                                    <div class="text-xs font-bold tracking-widest text-slate-500 uppercase">{{ __('Sources') }}</div>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm">
                                    <flux:icon.archive-box class="mb-4 size-8 text-brand-gold" />
                                    <div class="text-2xl font-bold">99%</div>
                                    <div class="text-xs font-bold tracking-widest text-slate-500 uppercase">{{ __('Accuracy') }}</div>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm">
                                    <flux:icon.shield-check class="mb-4 size-8 text-brand-red-bright" />
                                    <div class="text-2xl font-bold">Review</div>
                                    <div class="text-xs font-bold tracking-widest text-slate-500 uppercase">{{ __('Human Checked') }}</div>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm">
                                    <flux:icon.code-bracket class="mb-4 size-8 text-brand-red-bright" />
                                    <div class="text-2xl font-bold">API</div>
                                    <div class="text-xs font-bold tracking-widest text-slate-500 uppercase">{{ __('JSON Output') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="api" class="py-24 sm:py-32">
                <div class="app-container grid gap-16 lg:grid-cols-[0.8fr_1.2fr]">
                    <div>
                        <p class="app-label text-brand-red">{{ __('Developer First') }}</p>
                        <h2 class="mt-4 text-4xl font-extrabold tracking-tight text-brand-navy dark:text-white">
                            {{ __('Integration ready.') }}
                        </h2>
                        <p class="mt-6 text-lg leading-relaxed text-app-copy-muted">
                            {{ __('Query by year, state, or specific dates. Our REST API is versioned and designed for reliability in production applications.') }}
                        </p>

                        <div class="mt-10 space-y-4">
                            <div class="flex items-center gap-4 rounded-lg border border-app-outline p-4 transition-colors hover:bg-app-surface-low">
                                <div class="flex size-10 items-center justify-center rounded-lg bg-brand-navy/5 text-brand-navy dark:bg-white/5 dark:text-white">
                                    <flux:icon.code-bracket class="size-5" />
                                </div>
                                <div class="font-bold">{{ __('Versioned REST API endpoints') }}</div>
                            </div>
                            <div class="flex items-center gap-4 rounded-lg border border-app-outline p-4 transition-colors hover:bg-app-surface-low">
                                <div class="flex size-10 items-center justify-center rounded-lg bg-brand-navy/5 text-brand-navy dark:bg-white/5 dark:text-white">
                                    <flux:icon.variable class="size-5" />
                                </div>
                                <div class="font-bold">{{ __('ISO dates + consistent JSON errors') }}</div>
                            </div>
                            <div class="flex items-center gap-4 rounded-lg border border-app-outline p-4 transition-colors hover:bg-app-surface-low">
                                <div class="flex size-10 items-center justify-center rounded-lg bg-brand-navy/5 text-brand-navy dark:bg-white/5 dark:text-white">
                                    <flux:icon.arrows-up-down class="size-5" />
                                </div>
                                <div class="font-bold">{{ __('Public endpoints with no API key') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="app-code-block shadow-2xl">
                        <div class="mb-4 flex items-center justify-between">
                            <span class="flex items-center gap-2 font-mono text-xs font-bold text-slate-500 uppercase">
                                <span class="size-2 rounded-full bg-emerald-500"></span>
                                cURL / Terminal
                            </span>
                            <flux:button size="sm" variant="ghost" class="!bg-white/5 text-white hover:!bg-white/10">Copy</flux:button>
                        </div>
                        <pre class="overflow-x-auto font-mono text-sm leading-relaxed"><code><span class="text-brand-gold">curl</span> "{{ url('/api/v1/holidays/check?date=2026-05-30&state=SBH') }}"

{
  <span class="text-brand-red-bright">"date"</span>: "2026-05-30",
  <span class="text-brand-red-bright">"state_code"</span>: "SBH",
  <span class="text-brand-red-bright">"is_holiday"</span>: <span class="text-brand-gold">true</span>,
  <span class="text-brand-red-bright">"holidays"</span>: [
    {
      <span class="text-brand-red-bright">"name"</span>: "Pesta Kaamatan",
      <span class="text-brand-red-bright">"state_codes"</span>: ["SBH"],
      <span class="text-brand-red-bright">"scope"</span>: "state",
      <span class="text-brand-red-bright">"type"</span>: "state",
      <span class="text-brand-red-bright">"is_subject_to_change"</span>: <span class="text-brand-gold">false</span>
    }
  ]
}</code></pre>
                    </div>
                </div>
            </section>
            {{-- API Playground CTA --}}
            <section class="relative overflow-hidden border-y border-app-outline bg-app-surface-low py-24 sm:py-32">
                <div class="absolute inset-0 z-0 opacity-10 dark:opacity-20">
                    <div class="absolute -top-[20%] right-[10%] size-[40%] rounded-full bg-brand-red blur-[100px]"></div>
                    <div class="absolute -bottom-[20%] left-[5%] size-[35%] rounded-full bg-brand-gold blur-[100px]"></div>
                </div>

                <div class="app-container relative z-10">
                    <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
                        <div>
                            <p class="app-label text-brand-red">{{ __('Interactive') }}</p>
                            <h2 class="mt-4 text-4xl font-extrabold tracking-tight text-brand-navy dark:text-white sm:text-5xl">
                                {{ __('Try it in the') }} <span class="text-brand-red">{{ __('Playground') }}</span>.
                            </h2>
                            <p class="mt-6 text-lg leading-relaxed text-app-copy-muted">
                                {{ __('No Postman. No terminal. Just pick an endpoint, fill in your parameters, and see the live JSON response — right in your browser.') }}
                            </p>
                            <ul class="mt-8 space-y-3 text-sm text-app-copy-muted">
                                @foreach ([
                                    'Select endpoint from a list',
                                    'Configure year, state, date — via dropdowns',
                                    'See the live curl command update in real-time',
                                    'Copy the JSON response instantly',
                                ] as $item)
                                    <li class="flex items-center gap-3">
                                        <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-400/20">
                                            <svg class="size-3 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                        </span>
                                        {{ $item }}
                                    </li>
                                @endforeach
                            </ul>
                            <div class="mt-10">
                                <flux:button :href="route('api.playground')" variant="primary" class="!px-8 !py-4 text-base">
                                    {{ __('Open API Playground') }}
                                    <svg class="ml-2 size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                                </flux:button>
                            </div>
                        </div>

                        {{-- Playground preview card --}}
                        <div class="relative">
                            <div class="absolute -inset-4 rounded-[2rem] bg-brand-red/5 blur-2xl dark:bg-brand-red/10"></div>
                            <div class="relative overflow-hidden rounded-2xl border border-app-outline bg-app-surface shadow-xl">
                                {{-- Fake browser chrome --}}
                                <div class="flex items-center gap-3 border-b border-app-outline bg-app-surface-low px-4 py-3">
                                    <div class="flex gap-1.5">
                                        <div class="size-3 rounded-full bg-red-400/60"></div>
                                        <div class="size-3 rounded-full bg-amber-400/60"></div>
                                        <div class="size-3 rounded-full bg-emerald-400/60"></div>
                                    </div>
                                    <div class="flex-1 rounded-md bg-app-code px-3 py-1 font-mono text-xs text-slate-500">{{ url('/api/playground') }}</div>
                                </div>

                                {{-- Mock playground layout --}}
                                <div class="grid grid-cols-[140px_1fr] gap-0 divide-x divide-app-outline">
                                    {{-- Sidebar --}}
                                    <div class="space-y-1 p-3">
                                        <div class="rounded-md bg-brand-red/10 px-3 py-2">
                                            <div class="text-xs font-bold text-brand-red">GET /holidays</div>
                                        </div>
                                        <div class="px-3 py-2">
                                            <div class="text-xs text-app-copy-muted">GET /holidays/check</div>
                                        </div>
                                        <div class="px-3 py-2">
                                            <div class="text-xs text-app-copy-muted">GET /states</div>
                                        </div>
                                        <div class="mt-4 space-y-2">
                                            <div class="h-6 rounded-md bg-app-surface-low px-2 text-[10px] leading-6 text-app-copy-muted">year: 2026</div>
                                            <div class="h-6 rounded-md bg-app-surface-low px-2 text-[10px] leading-6 text-app-copy-muted">state: SBH</div>
                                        </div>
                                        <div class="mt-4">
                                            <div class="w-full rounded-lg bg-brand-red py-2 text-center text-xs font-bold text-white">Send →</div>
                                        </div>
                                    </div>
                                    {{-- Response --}}
                                    <div class="bg-app-code p-4">
                                        <div class="mb-2 flex items-center gap-2">
                                            <span class="rounded-full bg-emerald-400/20 px-2 py-0.5 font-mono text-[10px] font-bold text-emerald-400">HTTP 200</span>
                                        </div>
                                        <pre class="font-mono text-[10px] leading-relaxed text-slate-400"><code>{
  <span class="text-brand-gold">"data"</span>: [
    {
      <span class="text-slate-200">"name"</span>: <span class="text-emerald-400">"Pesta Kaamatan"</span>,
      <span class="text-slate-200">"date"</span>: <span class="text-emerald-400">"2026-05-30"</span>,
      <span class="text-slate-200">"scope"</span>: <span class="text-emerald-400">"state"</span>
    }
  ],
  <span class="text-brand-gold">"meta"</span>: {
    <span class="text-slate-200">"year"</span>: <span class="text-brand-gold">2026</span>,
    <span class="text-slate-200">"state"</span>: <span class="text-emerald-400">"SBH"</span>,
    <span class="text-slate-200">"count"</span>: <span class="text-brand-gold">1</span>
  }
}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

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
    </body>
</html>
