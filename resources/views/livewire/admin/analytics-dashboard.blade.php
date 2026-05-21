<div class="admin-page" wire:poll.30s>
    <!-- Header -->
    <div class="admin-header">
        <div>
            <div class="flex items-center gap-2">
                <div class="size-2 rounded-full bg-brand-red animate-pulse"></div>
                <p class="app-label text-brand-red">{{ __('System Telemetry') }}</p>
            </div>
            <h1 class="app-page-title mt-3">{{ __('Application Analytics') }}</h1>
            <p class="app-page-copy mt-2">{{ __('Real-time insights into web traffic, visitor metrics, and API request performance.') }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Timeframe Filter -->
            <div class="flex rounded-lg border border-app-outline bg-app-surface-low/30 p-1">
                <button 
                    type="button" 
                    wire:click="$set('timeframe', 'today')"
                    @class([
                        'px-3 py-1.5 rounded-md text-xs font-semibold transition-all cursor-pointer',
                        'bg-brand-red text-white shadow-sm' => $timeframe === 'today',
                        'text-app-copy-muted hover:text-app-copy' => $timeframe !== 'today'
                    ])
                >
                    {{ __('Today') }}
                </button>
                <button 
                    type="button" 
                    wire:click="$set('timeframe', '7days')"
                    @class([
                        'px-3 py-1.5 rounded-md text-xs font-semibold transition-all cursor-pointer',
                        'bg-brand-red text-white shadow-sm' => $timeframe === '7days',
                        'text-app-copy-muted hover:text-app-copy' => $timeframe !== '7days'
                    ])
                >
                    {{ __('7 Days') }}
                </button>
                <button 
                    type="button" 
                    wire:click="$set('timeframe', '30days')"
                    @class([
                        'px-3 py-1.5 rounded-md text-xs font-semibold transition-all cursor-pointer',
                        'bg-brand-red text-white shadow-sm' => $timeframe === '30days',
                        'text-app-copy-muted hover:text-app-copy' => $timeframe !== '30days'
                    ])
                >
                    {{ __('30 Days') }}
                </button>
            </div>

            <!-- Route Type Filter -->
            <div class="flex rounded-lg border border-app-outline bg-app-surface-low/30 p-1">
                <button 
                    type="button" 
                    wire:click="$set('routeType', 'all')"
                    @class([
                        'px-3 py-1.5 rounded-md text-xs font-semibold transition-all cursor-pointer',
                        'bg-brand-navy text-white dark:bg-white dark:text-brand-navy shadow-sm' => $routeType === 'all',
                        'text-app-copy-muted hover:text-app-copy' => $routeType !== 'all'
                    ])
                >
                    {{ __('All') }}
                </button>
                <button 
                    type="button" 
                    wire:click="$set('routeType', 'web')"
                    @class([
                        'px-3 py-1.5 rounded-md text-xs font-semibold transition-all cursor-pointer',
                        'bg-brand-navy text-white dark:bg-white dark:text-brand-navy shadow-sm' => $routeType === 'web',
                        'text-app-copy-muted hover:text-app-copy' => $routeType !== 'web'
                    ])
                >
                    {{ __('Web') }}
                </button>
                <button 
                    type="button" 
                    wire:click="$set('routeType', 'api')"
                    @class([
                        'px-3 py-1.5 rounded-md text-xs font-semibold transition-all cursor-pointer',
                        'bg-brand-navy text-white dark:bg-white dark:text-brand-navy shadow-sm' => $routeType === 'api',
                        'text-app-copy-muted hover:text-app-copy' => $routeType !== 'api'
                    ])
                >
                    {{ __('API') }}
                </button>
                <button 
                    type="button" 
                    wire:click="$set('routeType', 'admin')"
                    @class([
                        'px-3 py-1.5 rounded-md text-xs font-semibold transition-all cursor-pointer',
                        'bg-brand-navy text-white dark:bg-white dark:text-brand-navy shadow-sm' => $routeType === 'admin',
                        'text-app-copy-muted hover:text-app-copy' => $routeType !== 'admin'
                    ])
                >
                    {{ __('Console') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Core Statistics Cards -->
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
        <!-- Total Hits -->
        <div class="admin-stat-card group">
            <div class="absolute -right-4 -top-4 text-brand-navy/5 transition-transform group-hover:scale-110 group-hover:text-brand-navy/10 dark:text-white/5 dark:group-hover:text-white/10">
                <flux:icon.server class="size-24" />
            </div>
            <div class="relative z-10">
                <p class="app-label">{{ __('Total Requests') }}</p>
                <div class="mt-4 flex items-end justify-between">
                    <p class="text-4xl font-extrabold tracking-tight text-brand-navy dark:text-white">{{ number_format($totalRequests) }}</p>
                    <span class="app-badge app-badge-navy">{{ __('Traffic') }}</span>
                </div>
            </div>
        </div>

        <!-- Unique Visitors -->
        <div class="admin-stat-card group">
            <div class="absolute -right-4 -top-4 text-brand-gold/5 transition-transform group-hover:scale-110 group-hover:text-brand-gold/10">
                <flux:icon.users class="size-24" />
            </div>
            <div class="relative z-10">
                <p class="app-label">{{ __('Unique Visitors (IPs)') }}</p>
                <div class="mt-4 flex items-end justify-between">
                    <p class="text-4xl font-extrabold tracking-tight text-brand-navy dark:text-white">{{ number_format($uniqueVisitors) }}</p>
                    <span class="app-badge app-badge-gold">{{ __('Audience') }}</span>
                </div>
            </div>
        </div>

        <!-- API Requests -->
        <div class="admin-stat-card group">
            <div class="absolute -right-4 -top-4 text-brand-red/5 transition-transform group-hover:scale-110 group-hover:text-brand-red/10">
                <flux:icon.code-bracket-square class="size-24" />
            </div>
            <div class="relative z-10">
                <p class="app-label">{{ __('API Requests') }}</p>
                <div class="mt-4 flex items-end justify-between">
                    <p class="text-4xl font-extrabold tracking-tight text-brand-navy dark:text-white">{{ number_format($apiRequests) }}</p>
                    <span class="app-badge app-badge-red">{{ __('Endpoints') }}</span>
                </div>
            </div>
        </div>

        <!-- Average Response Time -->
        <div class="admin-stat-card group">
            <div class="absolute -right-4 -top-4 text-brand-navy/5 transition-transform group-hover:scale-110 group-hover:text-brand-navy/10 dark:text-white/5 dark:group-hover:text-white/10">
                <flux:icon.clock class="size-24" />
            </div>
            <div class="relative z-10">
                <p class="app-label">{{ __('Avg Latency') }}</p>
                <div class="mt-4 flex items-end justify-between">
                    <p class="text-4xl font-extrabold tracking-tight text-brand-navy dark:text-white">
                        {{ $avgResponseTime }} <span class="text-lg font-bold text-app-copy-muted">ms</span>
                    </p>
                    <span @class([
                        'app-badge',
                        'app-badge-navy' => $avgResponseTime < 200,
                        'app-badge-gold' => $avgResponseTime >= 200 && $avgResponseTime < 500,
                        'app-badge-red' => $avgResponseTime >= 500
                    ])>{{ __('Latency') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Chart Section -->
    <div class="grid gap-6">
        <section class="app-section relative">
            <div class="mb-4">
                <h2 class="text-lg font-bold text-brand-navy dark:text-white">{{ __('Traffic Volume') }}</h2>
                <p class="app-page-copy mt-1 text-xs">{{ __('Requests count by interval.') }}</p>
            </div>

            @php
                $maxVal = max(array_values($chartData)) ?: 1;
            @endphp

            <div class="relative mt-8 h-48 flex items-end gap-1 sm:gap-2 border-b border-app-outline pb-2">
                @foreach ($chartData as $label => $count)
                    @php
                        $percent = ($count / $maxVal) * 100;
                    @endphp
                    <div class="flex-1 flex flex-col items-center group h-full justify-end relative">
                        <!-- Tooltip on hover -->
                        <div class="absolute -top-10 scale-0 group-hover:scale-100 transition-all duration-200 bg-brand-navy text-white text-[10px] font-bold rounded py-1 px-2 shadow-lg z-20 dark:bg-white dark:text-brand-navy pointer-events-none whitespace-nowrap">
                            {{ number_format($count) }} reqs
                        </div>
                        
                        <!-- The Bar with gradient and subtle hover growth -->
                        <div 
                            class="w-full rounded-t bg-gradient-to-t from-brand-red/60 to-brand-red group-hover:from-brand-red group-hover:to-brand-red-bright transition-all duration-300 shadow-xs relative"
                            style="height: {{ max($percent, 2.5) }}%; min-height: 6px;"
                        >
                            <!-- Glow effect on hover -->
                            <div class="absolute inset-0 bg-white/20 opacity-0 group-hover:opacity-100 transition-opacity rounded-t"></div>
                        </div>

                        <!-- Date/Hour Label -->
                        <span class="text-[9px] font-bold text-app-copy-muted mt-2 text-center truncate w-full" title="{{ $label }}">
                            {{ $label }}
                        </span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <!-- Detailed Analytics Tables Grid -->
    <div class="grid gap-8 lg:grid-cols-2">
        <!-- Top API Endpoints -->
        <section class="app-section">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-brand-navy dark:text-white">{{ __('Top API Endpoints') }}</h2>
                    <p class="app-page-copy mt-1 text-xs">{{ __('Most active API routes ordered by total request volume.') }}</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-app-outline bg-app-surface-low/30">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Endpoint') }}</th>
                            <th class="text-right">{{ __('Hits') }}</th>
                            <th class="text-right">{{ __('Unique IPs') }}</th>
                            <th class="text-right">{{ __('Avg Latency') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topApiEndpoints as $endpoint)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="app-badge app-badge-red font-mono text-[9px] px-1 py-0.5">{{ $endpoint->method }}</span>
                                        <span class="font-mono font-semibold text-xs text-brand-navy dark:text-slate-300 truncate max-w-[150px] md:max-w-[200px]" title="{{ $endpoint->path }}">
                                            {{ $endpoint->path }}
                                        </span>
                                    </div>
                                </td>
                                <td class="text-right font-mono font-bold">{{ number_format($endpoint->count) }}</td>
                                <td class="text-right font-mono text-app-copy-muted">{{ number_format($endpoint->unique_ips) }}</td>
                                <td class="text-right font-mono text-app-copy-muted">{{ round($endpoint->avg_duration) }}ms</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-app-copy-muted text-xs">
                                    {{ __('No API request data available for this period.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Top Web Pages -->
        <section class="app-section">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-brand-navy dark:text-white">{{ __('Top Web Pages') }}</h2>
                    <p class="app-page-copy mt-1 text-xs">{{ __('Most popular pages visited via the browser interface.') }}</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-app-outline bg-app-surface-low/30">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>{{ __('Page Path') }}</th>
                            <th class="text-right">{{ __('Hits') }}</th>
                            <th class="text-right">{{ __('Unique IPs') }}</th>
                            <th class="text-right">{{ __('Avg Latency') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topWebPages as $page)
                            <tr>
                                <td>
                                    <span class="font-mono font-semibold text-xs text-brand-navy dark:text-slate-300 truncate max-w-[150px] md:max-w-[220px]" title="{{ $page->path }}">
                                        {{ $page->path }}
                                    </span>
                                </td>
                                <td class="text-right font-mono font-bold">{{ number_format($page->count) }}</td>
                                <td class="text-right font-mono text-app-copy-muted">{{ number_format($page->unique_ips) }}</td>
                                <td class="text-right font-mono text-app-copy-muted">{{ round($page->avg_duration) }}ms</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-app-copy-muted text-xs">
                                    {{ __('No page view data available for this period.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Top Visitors / Consumers -->
        <section class="app-section lg:col-span-2">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-brand-navy dark:text-white">{{ __('Top API Users / Visitors') }}</h2>
                    <p class="app-page-copy mt-1 text-xs">{{ __('Most active IP addresses and client browsers.') }}</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-app-outline bg-app-surface-low/30">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>{{ __('IP Address') }}</th>
                            <th class="text-right">{{ __('Requests') }}</th>
                            <th>{{ __('User Agent') }}</th>
                            <th class="text-right">{{ __('Last Active') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topConsumers as $consumer)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <flux:icon.computer-desktop class="size-4 text-app-copy-muted" />
                                        <span class="font-mono font-bold text-brand-red">{{ $consumer->ip_address }}</span>
                                    </div>
                                </td>
                                <td class="text-right font-mono font-bold">{{ number_format($consumer->count) }}</td>
                                <td class="max-w-xs truncate text-xs text-app-copy-muted" title="{{ $consumer->user_agent }}">
                                    {{ $consumer->user_agent }}
                                </td>
                                <td class="text-right font-mono text-xs text-app-copy-muted">
                                    {{ \Carbon\Carbon::parse($consumer->last_active)->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-app-copy-muted text-xs">
                                    {{ __('No consumer data available for this period.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <!-- Live Request Stream log -->
    <section class="app-section">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-extrabold tracking-tight text-brand-navy dark:text-white">{{ __('Live Request Stream') }}</h2>
                <p class="app-page-copy mt-1 text-xs">{{ __('Real-time log of incoming requests passing through the app.') }}</p>
            </div>
            
            <div class="max-w-xs w-full">
                <!-- Search input -->
                <flux:input 
                    type="search" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="{{ __('Search IP or path...') }}" 
                    icon="magnifying-glass"
                    size="sm"
                />
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-app-outline bg-app-surface-low/30">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>{{ __('Time') }}</th>
                        <th>{{ __('IP Address') }}</th>
                        <th>{{ __('Route Type') }}</th>
                        <th>{{ __('Request details') }}</th>
                        <th class="text-center">{{ __('Status') }}</th>
                        <th class="text-right">{{ __('Latency') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentLogs as $log)
                        <tr>
                            <td class="font-mono text-xs text-app-copy-muted">
                                {{ $log->created_at->format('H:i:s') }}
                                <span class="text-[9px] block">{{ $log->created_at->format('Y-M-d') }}</span>
                            </td>
                            <td>
                                <span class="font-mono font-semibold">{{ $log->ip_address }}</span>
                            </td>
                            <td>
                                <span @class([
                                    'app-badge',
                                    'app-badge-red' => $log->route_type === 'api',
                                    'app-badge-navy' => $log->route_type === 'web',
                                    'app-badge-gold' => $log->route_type === 'admin',
                                ])>{{ $log->route_type }}</span>
                            </td>
                            <td class="max-w-md">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[10px] font-bold uppercase font-mono px-1 py-0.5 bg-app-surface-muted rounded text-app-copy border border-app-outline">
                                        {{ $log->method }}
                                    </span>
                                    <span class="font-mono text-xs font-semibold text-brand-navy dark:text-slate-300 break-all select-all">
                                        {{ $log->path }}
                                    </span>
                                </div>
                            </td>
                            <td class="text-center">
                                @php
                                    $statusClass = match(true) {
                                        $log->status_code >= 200 && $log->status_code < 300 => 'bg-emerald-500/10 text-emerald-500 ring-emerald-500/20',
                                        $log->status_code >= 300 && $log->status_code < 400 => 'bg-blue-500/10 text-blue-500 ring-blue-500/20',
                                        $log->status_code >= 400 && $log->status_code < 500 => 'bg-brand-red/10 text-brand-red ring-brand-red/20',
                                        default => 'bg-red-600/15 text-red-600 ring-red-600/30'
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold ring-1 ring-inset {{ $statusClass }}">
                                    {{ $log->status_code }}
                                </span>
                            </td>
                            <td class="text-right">
                                @if($log->duration_ms !== null)
                                    @php
                                        $speedColor = match(true) {
                                            $log->duration_ms < 200 => 'text-emerald-500',
                                            $log->duration_ms >= 200 && $log->duration_ms < 500 => 'text-brand-gold',
                                            default => 'text-brand-red font-semibold'
                                        };
                                    @endphp
                                    <span class="font-mono text-xs {{ $speedColor }}">{{ $log->duration_ms }}ms</span>
                                @else
                                    <span class="text-app-copy-muted text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-app-copy-muted">
                                <flux:icon.server-stack class="mx-auto mb-3 size-10 opacity-20" />
                                <p class="font-medium">{{ __('No request logs match the criteria.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $recentLogs->links() }}
        </div>
    </section>
</div>
