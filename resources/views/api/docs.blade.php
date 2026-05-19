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
        <header class="border-b border-app-outline bg-app-surface">
            <div class="app-container flex h-16 items-center justify-between">
                <a href="{{ route('home') }}" class="font-bold text-brand-navy dark:text-white">{{ config('app.name', 'Malaysia Holiday API') }}</a>
                <a href="{{ route('home') }}#api" class="text-sm font-semibold text-app-copy-muted hover:text-brand-red">Back to Landing Page</a>
            </div>
        </header>

        <main class="py-12">
            <div class="app-container space-y-10">
                <section>
                    <h1 class="text-4xl font-extrabold tracking-tight text-brand-navy dark:text-white">API Documentation (v1)</h1>
                    <p class="mt-3 text-app-copy-muted">Base URL: <code>{{ url('/api/v1') }}</code></p>
                </section>

                <section class="app-card p-6">
                    <h2 class="text-2xl font-bold text-brand-navy dark:text-white">Authentication</h2>
                    <ul class="mt-4 list-disc space-y-2 pl-5 text-app-copy-muted">
                        <li><code>GET /api/v1/states</code> is public.</li>
                        <li><code>GET /api/v1/holidays</code> and <code>GET /api/v1/holidays/check</code> require header <code>X-API-Key</code>.</li>
                    </ul>
                    <pre class="mt-4 overflow-x-auto rounded-xl bg-app-code p-4 text-sm text-slate-200"><code>X-API-Key: {raw_api_key}</code></pre>
                </section>

                <section class="app-card p-6">
                    <h2 class="text-2xl font-bold text-brand-navy dark:text-white">Error Format</h2>
                    <pre class="mt-4 overflow-x-auto rounded-xl bg-app-code p-4 text-sm text-slate-200"><code>{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "year": ["The year field is required."]
    }
  }
}</code></pre>
                </section>

                <section class="space-y-6">
                    <h2 class="text-2xl font-bold text-brand-navy dark:text-white">Endpoints</h2>

                    <article class="app-card p-6">
                        <h3 class="text-xl font-bold">GET /api/v1/states</h3>
                        <p class="mt-2 text-app-copy-muted">Returns all supported Malaysia state and federal territory codes.</p>
                    </article>

                    <article class="app-card p-6">
                        <h3 class="text-xl font-bold">GET /api/v1/holidays</h3>
                        <p class="mt-2 text-app-copy-muted">Returns published holidays by year with optional filters.</p>
                        <pre class="mt-4 overflow-x-auto rounded-xl bg-app-code p-4 text-sm text-slate-200"><code>curl "{{ url('/api/v1/holidays?year=2026&state=SBH&include_source=1') }}" \
  -H "X-API-Key: your-key"</code></pre>
                    </article>

                    <article class="app-card p-6">
                        <h3 class="text-xl font-bold">GET /api/v1/holidays/check</h3>
                        <p class="mt-2 text-app-copy-muted">Checks whether a date is a holiday, optionally scoped to a state.</p>
                        <pre class="mt-4 overflow-x-auto rounded-xl bg-app-code p-4 text-sm text-slate-200"><code>curl "{{ url('/api/v1/holidays/check?date=2026-05-30&state=SBH') }}" \
  -H "X-API-Key: your-key"</code></pre>
                        <pre class="mt-4 overflow-x-auto rounded-xl bg-app-code p-4 text-sm text-slate-200"><code>{
  "date": "2026-05-30",
  "state_code": "SBH",
  "is_holiday": true,
  "holidays": [
    {
      "name": "Pesta Kaamatan",
      "state_codes": ["SBH"],
      "scope": "state",
      "type": "state",
      "is_subject_to_change": false
    }
  ]
}</code></pre>
                    </article>
                </section>

                <section class="app-card p-6">
                    <h2 class="text-2xl font-bold text-brand-navy dark:text-white">Error Codes</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full min-w-[480px] text-left text-sm">
                            <thead>
                                <tr class="border-b border-app-outline">
                                    <th class="py-2 pr-3">Code</th>
                                    <th class="py-2 pr-3">HTTP</th>
                                    <th class="py-2 pr-3">Meaning</th>
                                </tr>
                            </thead>
                            <tbody class="text-app-copy-muted">
                                <tr class="border-b border-app-outline/70"><td class="py-2 pr-3">VALIDATION_ERROR</td><td class="py-2 pr-3">422</td><td class="py-2 pr-3">Invalid request input</td></tr>
                                <tr class="border-b border-app-outline/70"><td class="py-2 pr-3">UNAUTHORIZED</td><td class="py-2 pr-3">401</td><td class="py-2 pr-3">Missing or invalid API key</td></tr>
                                <tr class="border-b border-app-outline/70"><td class="py-2 pr-3">NOT_FOUND</td><td class="py-2 pr-3">404</td><td class="py-2 pr-3">Endpoint/resource not found</td></tr>
                                <tr><td class="py-2 pr-3">TOO_MANY_REQUESTS</td><td class="py-2 pr-3">429</td><td class="py-2 pr-3">Rate limit exceeded</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>
