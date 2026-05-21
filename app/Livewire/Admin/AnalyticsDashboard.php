<?php

namespace App\Livewire\Admin;

use App\Models\RequestLog;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class AnalyticsDashboard extends Component
{
    use WithPagination;

    /**
     * Selected timeframe for analytics: today, 7days, 30days.
     */
    public string $timeframe = '7days';

    /**
     * Filter by route type: all, web, api, admin.
     */
    public string $routeType = 'all';

    /**
     * Search term for IP or Path.
     */
    public string $search = '';

    /**
     * Reset pagination when search is updated.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when route type is updated.
     */
    public function updatingRouteType(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when timeframe is updated.
     */
    public function updatingTimeframe(): void
    {
        $this->resetPage();
    }

    /**
     * Render the component with calculated statistics and logs.
     */
    public function render(): View
    {
        $startDate = match ($this->timeframe) {
            'today' => now()->startOfDay(),
            '30days' => now()->subDays(29)->startOfDay(),
            default => now()->subDays(6)->startOfDay(),
        };

        // 1. Get Core Statistics
        $statsQuery = RequestLog::query()
            ->where('created_at', '>=', $startDate)
            ->when($this->routeType !== 'all', function ($query): void {
                $query->where('route_type', $this->routeType);
            });

        $totalRequests = (clone $statsQuery)->count();
        $uniqueVisitors = (clone $statsQuery)->distinct()->count('ip_address');

        $apiRequests = (clone $statsQuery)
            ->where('route_type', 'api')
            ->count();

        $avgResponseTime = (clone $statsQuery)
            ->whereNotNull('duration_ms')
            ->avg('duration_ms') ?? 0;

        // 2. Generate Chart Data
        $driver = DB::connection()->getDriverName();
        if ($this->timeframe === 'today') {
            if ($driver === 'sqlite') {
                $logData = RequestLog::query()
                    ->where('created_at', '>=', $startDate)
                    ->when($this->routeType !== 'all', function ($query): void {
                        $query->where('route_type', $this->routeType);
                    })
                    ->selectRaw("cast(strftime('%H', created_at) as integer) as hour_num, COUNT(*) as count")
                    ->groupBy('hour_num')
                    ->pluck('count', 'hour_num');
            } else {
                $logData = RequestLog::query()
                    ->where('created_at', '>=', $startDate)
                    ->when($this->routeType !== 'all', function ($query): void {
                        $query->where('route_type', $this->routeType);
                    })
                    ->selectRaw('HOUR(created_at) as hour_num, COUNT(*) as count')
                    ->groupBy('hour_num')
                    ->pluck('count', 'hour_num');
            }

            $chartData = [];
            for ($i = 0; $i < 24; $i++) {
                $label = sprintf('%02d:00', $i);
                $chartData[$label] = $logData->get($i) ?? 0;
            }
        } else {
            if ($driver === 'sqlite') {
                $logData = RequestLog::query()
                    ->where('created_at', '>=', $startDate)
                    ->when($this->routeType !== 'all', function ($query): void {
                        $query->where('route_type', $this->routeType);
                    })
                    ->selectRaw("strftime('%Y-%m-%d', created_at) as log_date, COUNT(*) as count")
                    ->groupBy('log_date')
                    ->pluck('count', 'log_date');
            } else {
                $logData = RequestLog::query()
                    ->where('created_at', '>=', $startDate)
                    ->when($this->routeType !== 'all', function ($query): void {
                        $query->where('route_type', $this->routeType);
                    })
                    ->selectRaw('DATE(created_at) as log_date, COUNT(*) as count')
                    ->groupBy('log_date')
                    ->pluck('count', 'log_date');
            }

            $days = $this->timeframe === '30days' ? 30 : 7;
            $chartData = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $label = $date->format('M d');
                $chartData[$label] = $logData->get($date->toDateString()) ?? 0;
            }
        }

        // 3. Top API Endpoints
        $topApiEndpoints = RequestLog::query()
            ->where('route_type', 'api')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('path, method, count(*) as count, count(distinct ip_address) as unique_ips, avg(duration_ms) as avg_duration')
            ->groupBy('path', 'method')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // 4. Top Web Pages
        $topWebPages = RequestLog::query()
            ->where('route_type', 'web')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('path, count(*) as count, count(distinct ip_address) as unique_ips, avg(duration_ms) as avg_duration')
            ->groupBy('path')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // 5. Top Consumers (by IP)
        $topConsumers = RequestLog::query()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('ip_address, count(*) as count, max(created_at) as last_active, max(user_agent) as user_agent')
            ->groupBy('ip_address')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // 6. Recent Logs Stream (with search & routeType filters)
        $recentLogs = RequestLog::query()
            ->when($this->search, function ($query): void {
                $query->where(function ($sub): void {
                    $sub->where('ip_address', 'like', '%'.$this->search.'%')
                        ->orWhere('path', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->routeType !== 'all', function ($query): void {
                $query->where('route_type', $this->routeType);
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.analytics-dashboard', [
            'totalRequests' => $totalRequests,
            'uniqueVisitors' => $uniqueVisitors,
            'apiRequests' => $apiRequests,
            'avgResponseTime' => (int) round($avgResponseTime),
            'chartData' => $chartData,
            'topApiEndpoints' => $topApiEndpoints,
            'topWebPages' => $topWebPages,
            'topConsumers' => $topConsumers,
            'recentLogs' => $recentLogs,
        ])->layout('layouts.app');
    }
}
