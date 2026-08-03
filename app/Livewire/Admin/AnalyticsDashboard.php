<?php

namespace App\Livewire\Admin;

use App\Models\RequestLog;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class AnalyticsDashboard extends Component
{
    use WithPagination;

    /**
     * Selected timeframe for analytics: today, 7days, 30days, all, custom.
     */
    public string $timeframe = '7days';

    public bool $isCustomRangeOpen = false;

    public string $customStartDate = '';

    public string $customEndDate = '';

    #[Locked]
    public string $appliedStartDate = '';

    #[Locked]
    public string $appliedEndDate = '';

    /**
     * Filter by route type: all, web, api, admin.
     */
    public string $routeType = 'all';

    /**
     * Search term for IP or Path.
     */
    public string $search = '';

    public function mount(): void
    {
        $this->customStartDate = now()->subDays(6)->toDateString();
        $this->customEndDate = now()->toDateString();
        $this->appliedStartDate = $this->customStartDate;
        $this->appliedEndDate = $this->customEndDate;
    }

    public function openCustomRange(): void
    {
        $this->isCustomRangeOpen = true;
    }

    public function applyCustomRange(): void
    {
        $this->validate([
            'customStartDate' => ['required', 'date_format:Y-m-d', 'before_or_equal:customEndDate', 'before_or_equal:today'],
            'customEndDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:customStartDate', 'before_or_equal:today'],
        ]);

        $this->appliedStartDate = $this->customStartDate;
        $this->appliedEndDate = $this->customEndDate;
        $this->timeframe = 'custom';
        $this->isCustomRangeOpen = false;
        $this->resetPage();
    }

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
        [$startDate, $endDate] = $this->selectedPeriod();

        // 1. Get Core Statistics
        $statsQuery = $this->forSelectedPeriod(RequestLog::query(), $startDate, $endDate)
            ->when($this->routeType !== 'all', function ($query): void {
                $query->where('route_type', $this->routeType);
            });

        $totalRequests = (clone $statsQuery)->count();
        $anonymousVisitors = (clone $statsQuery)
            ->whereNotNull('visitor_hash')
            ->distinct()
            ->count('visitor_hash');

        $apiRequests = (clone $statsQuery)
            ->where('route_type', 'api')
            ->count();

        $avgResponseTime = (clone $statsQuery)
            ->whereNotNull('duration_ms')
            ->avg('duration_ms') ?? 0;

        // 2. Generate Chart Data
        $driver = DB::connection()->getDriverName();
        if ($this->timeframe === 'today') {
            $chartQuery = $this->forSelectedPeriod(RequestLog::query(), $startDate, $endDate)
                ->when($this->routeType !== 'all', function ($query): void {
                    $query->where('route_type', $this->routeType);
                });

            if ($driver === 'sqlite') {
                $logData = $chartQuery
                    ->selectRaw("cast(strftime('%H', created_at) as integer) as hour_num, COUNT(*) as count")
                    ->groupBy('hour_num')
                    ->pluck('count', 'hour_num');
            } else {
                $logData = $chartQuery
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
            $chartData = $this->chartDataForPeriod($driver, $startDate, $endDate);
        }

        // 3. Top API Endpoints
        $topApiEndpoints = $this->forSelectedPeriod(RequestLog::query(), $startDate, $endDate)
            ->where('route_type', 'api')
            ->selectRaw('path, method, count(*) as count, count(distinct visitor_hash) as anonymous_visitors, avg(duration_ms) as avg_duration')
            ->groupBy('path', 'method')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // 4. Top Web Pages
        $topWebPages = $this->forSelectedPeriod(RequestLog::query(), $startDate, $endDate)
            ->where('route_type', 'web')
            ->selectRaw('path, count(*) as count, count(distinct visitor_hash) as anonymous_visitors, avg(duration_ms) as avg_duration')
            ->groupBy('path')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // 5. Top Consumers (by daily anonymous visitor identifier)
        $topConsumers = $this->forSelectedPeriod(RequestLog::query(), $startDate, $endDate)
            ->whereNotNull('visitor_hash')
            ->selectRaw('visitor_hash, count(*) as count, max(created_at) as last_active, max(user_agent) as user_agent')
            ->groupBy('visitor_hash')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // 6. Recent Logs Stream (with search & routeType filters)
        $recentLogs = $this->forSelectedPeriod(RequestLog::query(), $startDate, $endDate)
            ->when($this->search, function ($query): void {
                $query->where(function ($sub): void {
                    $sub->where('path', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->routeType !== 'all', function ($query): void {
                $query->where('route_type', $this->routeType);
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.analytics-dashboard', [
            'totalRequests' => $totalRequests,
            'anonymousVisitors' => $anonymousVisitors,
            'apiRequests' => $apiRequests,
            'avgResponseTime' => (int) round($avgResponseTime),
            'chartData' => $chartData,
            'topApiEndpoints' => $topApiEndpoints,
            'topWebPages' => $topWebPages,
            'topConsumers' => $topConsumers,
            'recentLogs' => $recentLogs,
        ])->layout('layouts.app');
    }

    /**
     * @return array{0: CarbonInterface|null, 1: CarbonInterface|null}
     */
    private function selectedPeriod(): array
    {
        return match ($this->timeframe) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            '30days' => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
            'all' => [null, null],
            'custom' => [
                Carbon::createFromFormat('Y-m-d', $this->appliedStartDate)->startOfDay(),
                Carbon::createFromFormat('Y-m-d', $this->appliedEndDate)->endOfDay(),
            ],
            default => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
        };
    }

    private function forSelectedPeriod(Builder $query, ?CarbonInterface $startDate, ?CarbonInterface $endDate): Builder
    {
        return $query
            ->when($startDate, fn (Builder $query, CarbonInterface $date): Builder => $query->where('created_at', '>=', $date))
            ->when($endDate, fn (Builder $query, CarbonInterface $date): Builder => $query->where('created_at', '<=', $date));
    }

    /**
     * @return array<string, int>
     */
    private function chartDataForPeriod(string $driver, ?CarbonInterface $startDate, ?CarbonInterface $endDate): array
    {
        $chartQuery = $this->forSelectedPeriod(RequestLog::query(), $startDate, $endDate)
            ->when($this->routeType !== 'all', function (Builder $query): void {
                $query->where('route_type', $this->routeType);
            });

        $firstLogTimestamp = $startDate ? null : $chartQuery->clone()->min('created_at');
        $lastLogTimestamp = $endDate ? null : $chartQuery->clone()->max('created_at');
        $firstLogDate = $startDate ?? ($firstLogTimestamp ? Carbon::parse($firstLogTimestamp) : now());
        $lastLogDate = $endDate ?? ($lastLogTimestamp ? Carbon::parse($lastLogTimestamp) : now());
        $groupByMonth = $firstLogDate->diffInDays($lastLogDate) > 31;
        $dateExpression = $groupByMonth
            ? ($driver === 'sqlite' ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')")
            : ($driver === 'sqlite' ? "strftime('%Y-%m-%d', created_at)" : 'DATE(created_at)');

        $logData = $chartQuery
            ->selectRaw("{$dateExpression} as period, COUNT(*) as count")
            ->groupBy('period')
            ->pluck('count', 'period');

        $chartData = [];
        $cursor = $groupByMonth ? $firstLogDate->copy()->startOfMonth() : $firstLogDate->copy()->startOfDay();
        $finalDate = $groupByMonth ? $lastLogDate->copy()->startOfMonth() : $lastLogDate->copy()->startOfDay();

        while ($cursor->lte($finalDate)) {
            $key = $cursor->format($groupByMonth ? 'Y-m' : 'Y-m-d');
            $label = $cursor->format($groupByMonth ? 'M Y' : 'M d');
            $chartData[$label] = (int) ($logData->get($key) ?? 0);
            $cursor = $groupByMonth ? $cursor->addMonth() : $cursor->addDay();
        }

        return $chartData;
    }
}
