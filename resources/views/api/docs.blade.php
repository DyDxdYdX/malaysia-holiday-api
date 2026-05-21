@php
    $baseUrl = url('/api/v1');
    $stateCodes = [
        ['code' => 'JHR', 'name' => 'Johor'],
        ['code' => 'KDH', 'name' => 'Kedah'],
        ['code' => 'KTN', 'name' => 'Kelantan'],
        ['code' => 'MLK', 'name' => 'Melaka'],
        ['code' => 'NSN', 'name' => 'Negeri Sembilan'],
        ['code' => 'PHG', 'name' => 'Pahang'],
        ['code' => 'PRK', 'name' => 'Perak'],
        ['code' => 'PLS', 'name' => 'Perlis'],
        ['code' => 'PNG', 'name' => 'Pulau Pinang'],
        ['code' => 'SBH', 'name' => 'Sabah'],
        ['code' => 'SWK', 'name' => 'Sarawak'],
        ['code' => 'SGR', 'name' => 'Selangor'],
        ['code' => 'TRG', 'name' => 'Terengganu'],
        ['code' => 'KUL', 'name' => 'Wilayah Persekutuan Kuala Lumpur'],
        ['code' => 'LBN', 'name' => 'Wilayah Persekutuan Labuan'],
        ['code' => 'PJY', 'name' => 'Wilayah Persekutuan Putrajaya'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>API Documentation - {{ config('app.name', 'Malaysia Holiday API') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="app-shell antialiased">
        <header class="sticky top-0 z-40 border-b border-app-outline bg-app-surface/90 backdrop-blur">
            <div class="app-container flex h-16 items-center justify-between gap-4">
                <a href="{{ route('home') }}" class="font-bold text-brand-navy dark:text-white">{{ config('app.name', 'Malaysia Holiday API') }}</a>
                <nav class="flex items-center gap-5 text-sm font-semibold text-app-copy-muted">
                    <a href="#endpoints" class="hidden transition-colors hover:text-brand-red sm:inline">Endpoints</a>
                    <a href="#state-codes" class="hidden transition-colors hover:text-brand-red sm:inline">State Codes</a>
                    <a href="{{ route('api.playground') }}" class="hidden transition-colors hover:text-brand-red sm:inline">Playground</a>
                    <a href="{{ route('home') }}" class="transition-colors hover:text-brand-red">Home</a>
                </nav>
            </div>
        </header>

        <main>
            <section class="border-b border-app-outline bg-app-surface py-12 sm:py-16">
                <div class="app-container grid gap-8 lg:grid-cols-[1fr_360px] lg:items-end">
                    <div>
                        <p class="app-label text-brand-red">REST API v1</p>
                        <h1 class="mt-3 text-4xl font-extrabold tracking-tight text-brand-navy dark:text-white sm:text-5xl">API Documentation (v1)</h1>
                        <p class="mt-4 max-w-3xl text-lg leading-relaxed text-app-copy-muted">
                            Public Malaysia holiday data by year, state, and date. All endpoints return JSON and only expose published holiday records.
                        </p>
                    </div>

                    <div class="app-card p-5">
                        <div class="text-xs font-bold tracking-widest text-app-copy-muted uppercase">Base URL</div>
                        <code class="mt-2 block overflow-x-auto rounded-lg bg-app-code px-3 py-2 text-sm text-slate-100">{{ $baseUrl }}</code>
                        <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <div class="font-bold text-brand-navy dark:text-white">Auth</div>
                                <div class="text-app-copy-muted">None</div>
                            </div>
                            <div>
                                <div class="font-bold text-brand-navy dark:text-white">Format</div>
                                <div class="text-app-copy-muted">JSON</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="py-10">
                <div class="app-container grid gap-6 md:grid-cols-3">
                    <div class="app-card p-5">
                        <div class="text-sm font-bold text-brand-navy dark:text-white">No API key</div>
                        <p class="mt-2 text-sm leading-relaxed text-app-copy-muted">Public consumers do not need an account, bearer token, or request header.</p>
                    </div>
                    <div class="app-card p-5">
                        <div class="text-sm font-bold text-brand-navy dark:text-white">Published data only</div>
                        <p class="mt-2 text-sm leading-relaxed text-app-copy-muted">Draft and rejected records are excluded from every API response.</p>
                    </div>
                    <div class="app-card p-5">
                        <div class="text-sm font-bold text-brand-navy dark:text-white">Malaysia coverage</div>
                        <p class="mt-2 text-sm leading-relaxed text-app-copy-muted">Supports all 13 states and 3 federal territories.</p>
                    </div>
                </div>
            </section>

            <section id="endpoints" class="border-y border-app-outline bg-app-surface-low py-12">
                <div class="app-container space-y-8">
                    <div>
                        <p class="app-label text-brand-red">Endpoints</p>
                        <h2 class="mt-2 text-3xl font-extrabold text-brand-navy dark:text-white">Reference</h2>
                    </div>

                    <article id="states" class="app-card overflow-hidden">
                        <div class="border-b border-app-outline p-6">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="rounded-md bg-emerald-100 px-2 py-1 font-mono text-xs font-bold text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-200">GET</span>
                                <h3 class="font-mono text-xl font-bold text-brand-navy dark:text-white">/api/v1/states</h3>
                            </div>
                            <p class="mt-3 text-app-copy-muted">Returns every supported Malaysia state and federal territory code.</p>
                        </div>
                        <div class="grid gap-6 p-6 lg:grid-cols-2">
                            <div>
                                <h4 class="font-bold text-brand-navy dark:text-white">Request</h4>
                                <pre class="mt-3 overflow-x-auto rounded-lg bg-app-code p-4 text-sm text-slate-100"><code>curl "{{ $baseUrl }}/states"</code></pre>
                            </div>
                            <div>
                                <h4 class="font-bold text-brand-navy dark:text-white">Response 200</h4>
                                <pre class="mt-3 overflow-x-auto rounded-lg bg-app-code p-4 text-sm text-slate-100"><code>{
  "data": [
    { "code": "JHR", "name": "Johor" },
    { "code": "SBH", "name": "Sabah" },
    { "code": "KUL", "name": "Wilayah Persekutuan Kuala Lumpur" }
  ]
}</code></pre>
                            </div>
                        </div>
                        <div class="border-t border-app-outline px-6 py-4 flex justify-end">
                            <a href="{{ route('api.playground') }}" class="inline-flex items-center gap-2 rounded-lg border border-app-outline px-4 py-2 text-sm font-bold text-brand-red transition hover:bg-app-surface-low">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                                Try in Playground
                            </a>
                        </div>
                    </article>

                    <article id="holidays" class="app-card overflow-hidden">
                        <div class="border-b border-app-outline p-6">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="rounded-md bg-emerald-100 px-2 py-1 font-mono text-xs font-bold text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-200">GET</span>
                                <h3 class="font-mono text-xl font-bold text-brand-navy dark:text-white">/api/v1/holidays</h3>
                            </div>
                            <p class="mt-3 text-app-copy-muted">Returns published holidays for a required year, with optional state and source filters.</p>
                        </div>
                        <div class="space-y-6 p-6">
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[760px] text-left text-sm">
                                    <thead>
                                        <tr class="border-b border-app-outline text-brand-navy dark:text-white">
                                            <th class="py-2 pr-4">Parameter</th>
                                            <th class="py-2 pr-4">Required</th>
                                            <th class="py-2 pr-4">Allowed values</th>
                                            <th class="py-2 pr-4">Description</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-app-copy-muted">
                                        <tr class="border-b border-app-outline/70"><td class="py-3 pr-4 font-mono">year</td><td class="py-3 pr-4">Yes</td><td class="py-3 pr-4">2000..2100</td><td class="py-3 pr-4">Holiday year.</td></tr>
                                        <tr class="border-b border-app-outline/70"><td class="py-3 pr-4 font-mono">state</td><td class="py-3 pr-4">No</td><td class="py-3 pr-4">State code or FED</td><td class="py-3 pr-4">Filters records to one state or territory.</td></tr>
                                        <tr><td class="py-3 pr-4 font-mono">include_source</td><td class="py-3 pr-4">No</td><td class="py-3 pr-4">true, false, 1, 0</td><td class="py-3 pr-4">Includes source metadata when truthy.</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="grid gap-6 lg:grid-cols-2">
                                <div>
                                    <h4 class="font-bold text-brand-navy dark:text-white">Request</h4>
                                    <pre class="mt-3 overflow-x-auto rounded-lg bg-app-code p-4 text-sm text-slate-100"><code>curl "{{ $baseUrl }}/holidays?year=2026&state=SBH&include_source=1"</code></pre>
                                </div>
                                <div>
                                    <h4 class="font-bold text-brand-navy dark:text-white">Response 200 <span class="text-xs font-normal text-app-copy-muted">(with state filter)</span></h4>
                                    <pre class="mt-3 overflow-x-auto rounded-lg bg-app-code p-4 text-sm text-slate-100"><code>{
  "data": [
    {
      "name": "Pesta Kaamatan",
      "date": "2026-05-30",
      "day_name": "Saturday",
      "is_subject_to_change": false,
      "source": {
        "source_name": "JPM HKA 2026",
        "source_type": "federal_pdf",
        "source_url": "https://example.gov.my/hka2026.pdf",
        "year": 2026,
        "uploaded_at": "2026-05-19T08:00:00+08:00"
      }
    }
  ],
  "meta": {
    "year": 2026,
    "state": "SBH",
    "count": 1
  }
}</code></pre>
                                    <p class="mt-3 text-xs text-app-copy-muted">Without <code>state</code> filter, each item also includes a <code>state_codes</code> array.</p>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <a href="{{ route('api.playground') }}?endpoint=holidays" class="inline-flex items-center gap-2 rounded-lg border border-app-outline px-4 py-2 text-sm font-bold text-brand-red transition hover:bg-app-surface-low">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                                    Try in Playground
                                </a>
                            </div>
                        </div>
                    </article>

                    <article id="holiday-check" class="app-card overflow-hidden">
                        <div class="border-b border-app-outline p-6">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="rounded-md bg-emerald-100 px-2 py-1 font-mono text-xs font-bold text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-200">GET</span>
                                <h3 class="font-mono text-xl font-bold text-brand-navy dark:text-white">/api/v1/holidays/check</h3>
                            </div>
                            <p class="mt-3 text-app-copy-muted">Checks whether a specific date is a published holiday, optionally scoped to a state.</p>
                        </div>
                        <div class="space-y-6 p-6">
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[640px] text-left text-sm">
                                    <thead>
                                        <tr class="border-b border-app-outline text-brand-navy dark:text-white">
                                            <th class="py-2 pr-4">Parameter</th>
                                            <th class="py-2 pr-4">Required</th>
                                            <th class="py-2 pr-4">Allowed values</th>
                                            <th class="py-2 pr-4">Description</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-app-copy-muted">
                                        <tr class="border-b border-app-outline/70"><td class="py-3 pr-4 font-mono">date</td><td class="py-3 pr-4">Yes</td><td class="py-3 pr-4">YYYY-MM-DD</td><td class="py-3 pr-4">Date to check.</td></tr>
                                        <tr><td class="py-3 pr-4 font-mono">state</td><td class="py-3 pr-4">No</td><td class="py-3 pr-4">State code or FED</td><td class="py-3 pr-4">Limits the check to one state or territory.</td></tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="grid gap-6 lg:grid-cols-2">
                                <div>
                                    <h4 class="font-bold text-brand-navy dark:text-white">Request</h4>
                                    <pre class="mt-3 overflow-x-auto rounded-lg bg-app-code p-4 text-sm text-slate-100"><code>curl "{{ $baseUrl }}/holidays/check?date=2026-05-30&state=SBH"</code></pre>
                                </div>
                                <div>
                                    <h4 class="font-bold text-brand-navy dark:text-white">Response 200</h4>
                                    <pre class="mt-3 overflow-x-auto rounded-lg bg-app-code p-4 text-sm text-slate-100"><code>{
  "date": "2026-05-30",
  "state_code": "SBH",
  "is_holiday": true,
  "holidays": [
    {
      "name": "Pesta Kaamatan",
      "state_codes": ["SBH"],
      "is_subject_to_change": false
    }
  ]
}</code></pre>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <a href="{{ route('api.playground') }}" class="inline-flex items-center gap-2 rounded-lg border border-app-outline px-4 py-2 text-sm font-bold text-brand-red transition hover:bg-app-surface-low">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                                    Try in Playground
                                </a>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <section id="state-codes" class="py-12">
                <div class="app-container grid gap-8 lg:grid-cols-[320px_1fr]">
                    <div>
                        <p class="app-label text-brand-red">State Codes</p>
                        <h2 class="mt-2 text-3xl font-extrabold text-brand-navy dark:text-white">Supported regions</h2>
                        <p class="mt-3 text-app-copy-muted">Use these codes in the <code>state</code> query parameter.</p>
                    </div>
                    <div class="app-card overflow-hidden">
                        <div class="grid divide-y divide-app-outline md:grid-cols-2 md:divide-x md:divide-y-0">
                            @foreach (array_chunk($stateCodes, 8) as $chunk)
                                <div class="divide-y divide-app-outline">
                                    @foreach ($chunk as $state)
                                        <div class="flex items-center justify-between gap-4 px-5 py-3 text-sm">
                                            <span class="text-app-copy-muted">{{ $state['name'] }}</span>
                                            <code class="rounded-md bg-app-surface-low px-2 py-1 font-bold text-brand-navy dark:bg-white/5 dark:text-white">{{ $state['code'] }}</code>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <section id="errors" class="border-t border-app-outline bg-app-surface-low py-12">
                <div class="app-container grid gap-8 lg:grid-cols-[320px_1fr]">
                    <div>
                        <p class="app-label text-brand-red">Errors</p>
                        <h2 class="mt-2 text-3xl font-extrabold text-brand-navy dark:text-white">Response format</h2>
                        <p class="mt-3 text-app-copy-muted">Validation and routing errors use the same JSON envelope.</p>
                    </div>
                    <div class="space-y-6">
                        <pre class="overflow-x-auto rounded-lg bg-app-code p-4 text-sm text-slate-100"><code>{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "year": ["The year field is required."]
    }
  },
  "errors": {
    "year": ["The year field is required."]
  }
}</code></pre>

                        <div class="app-card overflow-hidden">
                            <table class="w-full min-w-[520px] text-left text-sm">
                                <thead>
                                    <tr class="border-b border-app-outline text-brand-navy dark:text-white">
                                        <th class="px-5 py-3">Code</th>
                                        <th class="px-5 py-3">HTTP</th>
                                        <th class="px-5 py-3">Meaning</th>
                                    </tr>
                                </thead>
                                <tbody class="text-app-copy-muted">
                                    <tr class="border-b border-app-outline/70"><td class="px-5 py-3 font-mono">VALIDATION_ERROR</td><td class="px-5 py-3">422</td><td class="px-5 py-3">Request parameters failed validation.</td></tr>
                                    <tr class="border-b border-app-outline/70"><td class="px-5 py-3 font-mono">NOT_FOUND</td><td class="px-5 py-3">404</td><td class="px-5 py-3">Endpoint or resource was not found.</td></tr>
                                    <tr><td class="px-5 py-3 font-mono">METHOD_NOT_ALLOWED</td><td class="px-5 py-3">405</td><td class="px-5 py-3">HTTP method is not supported for the endpoint.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
