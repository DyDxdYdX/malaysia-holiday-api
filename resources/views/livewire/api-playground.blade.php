<div
    x-data="{
        copyUrl() {
            navigator.clipboard.writeText($wire.requestUrl);
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        },
        copyResponse() {
            if ($wire.responseJson) {
                navigator.clipboard.writeText($wire.responseJson);
                this.responseCopied = true;
                setTimeout(() => this.responseCopied = false, 2000);
            }
        },
        copied: false,
        responseCopied: false,
    }"
    class="grid gap-8 lg:grid-cols-[380px_1fr] lg:items-start"
>
    {{-- ─── Left: Controls Panel ──────────────────────────────────────── --}}
    <div class="space-y-6">
        {{-- Endpoint Selector --}}
        <div class="app-card overflow-hidden">
            <div class="border-b border-app-outline bg-app-surface-low px-5 py-3">
                <p class="text-xs font-bold tracking-widest text-app-copy-muted uppercase">{{ __('Endpoint') }}</p>
            </div>
            <div class="divide-y divide-app-outline">
                @foreach ([
                    ['holidays',       'GET /holidays',       'Retrieve holidays by year and optional filters.'],
                    ['holidays/check', 'GET /holidays/check', 'Check if a specific date is a public holiday.'],
                    ['states',         'GET /states',         'List all Malaysia state and territory codes.'],
                ] as [$value, $label, $description])
                    <label
                        for="endpoint-{{ $loop->index }}"
                        class="flex cursor-pointer items-start gap-4 px-5 py-4 transition-colors hover:bg-app-surface-low {{ $endpoint === $value ? 'bg-app-surface-low' : '' }}"
                    >
                        <input
                            id="endpoint-{{ $loop->index }}"
                            type="radio"
                            wire:model.live="endpoint"
                            value="{{ $value }}"
                            class="mt-0.5 shrink-0 accent-brand-red"
                        />
                        <div>
                            <div class="font-mono text-sm font-bold text-brand-navy dark:text-white">{{ $label }}</div>
                            <div class="mt-0.5 text-xs text-app-copy-muted">{{ $description }}</div>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Parameters --}}
        @if ($endpoint !== 'states')
            <div class="app-card overflow-hidden">
                <div class="border-b border-app-outline bg-app-surface-low px-5 py-3">
                    <p class="text-xs font-bold tracking-widest text-app-copy-muted uppercase">{{ __('Parameters') }}</p>
                </div>
                <div class="space-y-4 p-5">
                    {{-- /holidays params --}}
                    @if ($endpoint === 'holidays')
                        <div>
                            <label for="pg-year" class="mb-1.5 block text-xs font-bold text-brand-navy dark:text-white">
                                year <span class="ml-1 text-brand-red">*</span>
                            </label>
                            <input
                                id="pg-year"
                                type="number"
                                wire:model.live="year"
                                min="2000"
                                max="2100"
                                placeholder="e.g. 2026"
                                class="w-full rounded-lg border border-app-outline bg-app-surface px-3 py-2 font-mono text-sm text-brand-navy focus:border-brand-red focus:ring-2 focus:ring-brand-red/20 focus:outline-none dark:text-white"
                            />
                        </div>

                        <div>
                            <label for="pg-state" class="mb-1.5 block text-xs font-bold text-brand-navy dark:text-white">state</label>
                            <select
                                id="pg-state"
                                wire:model.live="state"
                                class="w-full rounded-lg border border-app-outline bg-app-surface px-3 py-2 font-mono text-sm text-brand-navy focus:border-brand-red focus:ring-2 focus:ring-brand-red/20 focus:outline-none dark:text-white"
                            >
                                <option value="">— any state —</option>
                                @foreach ($stateOptions as $code => $name)
                                    <option value="{{ $code }}">{{ $code }} – {{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="pg-scope" class="mb-1.5 block text-xs font-bold text-brand-navy dark:text-white">scope</label>
                            <select
                                id="pg-scope"
                                wire:model.live="scope"
                                class="w-full rounded-lg border border-app-outline bg-app-surface px-3 py-2 font-mono text-sm text-brand-navy focus:border-brand-red focus:ring-2 focus:ring-brand-red/20 focus:outline-none dark:text-white"
                            >
                                <option value="">— any scope —</option>
                                @foreach ($scopeOptions as $opt)
                                    <option value="{{ $opt }}">{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="pg-type" class="mb-1.5 block text-xs font-bold text-brand-navy dark:text-white">type</label>
                            <select
                                id="pg-type"
                                wire:model.live="type"
                                class="w-full rounded-lg border border-app-outline bg-app-surface px-3 py-2 font-mono text-sm text-brand-navy focus:border-brand-red focus:ring-2 focus:ring-brand-red/20 focus:outline-none dark:text-white"
                            >
                                <option value="">— any type —</option>
                                @foreach ($typeOptions as $opt)
                                    <option value="{{ $opt }}">{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>

                        <label for="pg-include-source" class="flex cursor-pointer items-center gap-3 pt-1">
                            <input
                                id="pg-include-source"
                                type="checkbox"
                                wire:model.live="includeSource"
                                class="size-4 rounded accent-brand-red"
                            />
                            <span class="text-xs font-bold text-brand-navy dark:text-white">include_source</span>
                        </label>
                    @endif

                    {{-- /holidays/check params --}}
                    @if ($endpoint === 'holidays/check')
                        <div>
                            <label for="pg-date" class="mb-1.5 block text-xs font-bold text-brand-navy dark:text-white">
                                date <span class="ml-1 text-brand-red">*</span>
                            </label>
                            <input
                                id="pg-date"
                                type="date"
                                wire:model.live="date"
                                class="w-full rounded-lg border border-app-outline bg-app-surface px-3 py-2 font-mono text-sm text-brand-navy focus:border-brand-red focus:ring-2 focus:ring-brand-red/20 focus:outline-none dark:text-white"
                            />
                        </div>

                        <div>
                            <label for="pg-check-state" class="mb-1.5 block text-xs font-bold text-brand-navy dark:text-white">state</label>
                            <select
                                id="pg-check-state"
                                wire:model.live="state"
                                class="w-full rounded-lg border border-app-outline bg-app-surface px-3 py-2 font-mono text-sm text-brand-navy focus:border-brand-red focus:ring-2 focus:ring-brand-red/20 focus:outline-none dark:text-white"
                            >
                                <option value="">— any state —</option>
                                @foreach ($stateOptions as $code => $name)
                                    <option value="{{ $code }}">{{ $code }} – {{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Send Button --}}
        <button
            id="pg-send-btn"
            wire:click="sendRequest"
            wire:loading.attr="disabled"
            class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-red px-6 py-3.5 font-bold text-white shadow-lg transition hover:bg-brand-red/90 active:scale-95 disabled:opacity-60"
        >
            <span wire:loading.remove wire:target="sendRequest">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
            </span>
            <span wire:loading wire:target="sendRequest">
                <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            </span>
            <span wire:loading.remove wire:target="sendRequest">{{ __('Send Request') }}</span>
            <span wire:loading wire:target="sendRequest">{{ __('Sending…') }}</span>
        </button>
    </div>

    {{-- ─── Right: Request + Response ────────────────────────────────── --}}
    <div class="space-y-6">
        {{-- Request URL --}}
        <div class="app-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-app-outline bg-app-surface-low px-5 py-3">
                <p class="text-xs font-bold tracking-widest text-app-copy-muted uppercase">{{ __('Request URL') }}</p>
                <button
                    @click="copyUrl"
                    class="flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-bold text-app-copy-muted transition hover:bg-app-outline hover:text-brand-navy dark:hover:text-white"
                >
                    <template x-if="!copied">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" /></svg>
                    </template>
                    <template x-if="copied">
                        <svg class="size-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    </template>
                    <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                </button>
            </div>
            <div class="p-5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-md bg-emerald-100 px-2 py-1 font-mono text-xs font-bold text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-200">GET</span>
                    <code class="break-all font-mono text-sm text-brand-navy dark:text-white">{{ $this->requestUrl }}</code>
                </div>

                <div class="mt-4 rounded-lg bg-app-code px-4 py-3">
                    <p class="mb-1 text-[10px] font-bold tracking-widest text-slate-500 uppercase">curl</p>
                    <code class="break-all font-mono text-xs text-slate-300">{{ $this->curlCommand }}</code>
                </div>
            </div>
        </div>

        {{-- Response Panel --}}
        <div class="app-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-app-outline bg-app-surface-low px-5 py-3">
                <div class="flex items-center gap-3">
                    <p class="text-xs font-bold tracking-widest text-app-copy-muted uppercase">{{ __('Response') }}</p>
                    @if ($responseStatus)
                        <span
                            class="rounded-full px-2 py-0.5 font-mono text-xs font-bold
                                {{ $responseStatus >= 200 && $responseStatus < 300
                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-300'
                                    : 'bg-red-100 text-red-800 dark:bg-red-400/15 dark:text-red-300' }}"
                        >
                            HTTP {{ $responseStatus }}
                        </span>
                    @endif
                </div>
                @if ($responseJson)
                    <button
                        @click="copyResponse"
                        class="flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-bold text-app-copy-muted transition hover:bg-app-outline hover:text-brand-navy dark:hover:text-white"
                    >
                        <template x-if="!responseCopied">
                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" /></svg>
                        </template>
                        <template x-if="responseCopied">
                            <svg class="size-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        </template>
                        <span x-text="responseCopied ? 'Copied!' : 'Copy JSON'"></span>
                    </button>
                @endif
            </div>

            <div class="min-h-[320px]">
                {{-- Idle state --}}
                @if (! $responseJson && ! $errorMessage && ! $isLoading)
                    <div class="flex flex-col items-center justify-center gap-4 py-20 text-center text-app-copy-muted">
                        <div class="flex size-14 items-center justify-center rounded-full bg-app-surface-low">
                            <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                        </div>
                        <p class="max-w-xs text-sm">{{ __('Configure your parameters and hit "Send Request" to see the live API response here.') }}</p>
                    </div>
                @endif

                {{-- Loading state --}}
                <div wire:loading wire:target="sendRequest" class="flex flex-col items-center justify-center gap-4 py-20 text-center text-app-copy-muted">
                    <svg class="size-8 animate-spin text-brand-red" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <p class="text-sm">{{ __('Sending request…') }}</p>
                </div>

                {{-- Error state --}}
                @if ($errorMessage)
                    <div class="m-5 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-400/20 dark:bg-red-400/10">
                        <p class="text-sm font-bold text-red-700 dark:text-red-400">{{ __('Request Error') }}</p>
                        <p class="mt-1 font-mono text-xs text-red-600 dark:text-red-300">{{ $errorMessage }}</p>
                    </div>
                @endif

                {{-- Response JSON --}}
                @if ($responseJson)
                    <div wire:loading.remove wire:target="sendRequest">
                        <pre
                            id="pg-response-json"
                            class="overflow-x-auto rounded-none bg-app-code p-5 font-mono text-sm leading-relaxed text-slate-300"
                        >{{ $responseJson }}</pre>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
