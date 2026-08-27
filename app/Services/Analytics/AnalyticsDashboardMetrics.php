<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsDailyPathStat;
use App\Models\AnalyticsDailyRouteStat;
use App\Models\AnalyticsDailyVisitorStat;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsDashboardMetrics
{
    /**
     * @return array{
     *     totalRequests: int,
     *     anonymousVisitors: int,
     *     apiRequests: int,
     *     avgResponseTime: int,
     *     chartData: array<string, int>,
     *     topApiEndpoints: Collection<int, object>,
     *     topWebPages: Collection<int, object>,
     *     topConsumers: Collection<int, object>
     * }
     */
    public function snapshot(string $timeframe, string $routeType, ?CarbonInterface $startDate, ?CarbonInterface $endDate): array
    {
        if ($timeframe === 'today') {
            return $this->todaySnapshot($routeType, $startDate ?? now()->startOfDay(), $endDate ?? now()->endOfDay());
        }

        [$historicalStart, $historicalEnd] = $this->historicalBounds($startDate, $endDate);
        $includesToday = $this->includesToday($startDate, $endDate);
        $todayStart = now()->startOfDay();
        $todayEnd = $includesToday ? ($endDate ?? now()->endOfDay()) : null;

        $historicalTotals = $historicalStart && $historicalEnd
            ? $this->historicalTotals($historicalStart, $historicalEnd, $routeType)
            : $this->emptyTotals();

        $todayTotals = $todayStart && $todayEnd
            ? $this->rawTotals($todayStart, $todayEnd, $routeType)
            : $this->emptyTotals();

        $durationTotal = $historicalTotals['duration_total_ms'] + $todayTotals['duration_total_ms'];
        $durationSamples = $historicalTotals['duration_samples'] + $todayTotals['duration_samples'];

        return [
            'totalRequests' => $historicalTotals['request_count'] + $todayTotals['request_count'],
            'anonymousVisitors' => $historicalTotals['unique_visitors'] + $todayTotals['unique_visitors'],
            'apiRequests' => $historicalTotals['api_requests'] + $todayTotals['api_requests'],
            'avgResponseTime' => $durationSamples > 0 ? (int) round($durationTotal / $durationSamples) : 0,
            'chartData' => $this->chartDataForPeriod($routeType, $startDate, $endDate, $historicalStart, $historicalEnd, $includesToday),
            'topApiEndpoints' => $this->topApiEndpoints($historicalStart, $historicalEnd, $todayStart, $todayEnd),
            'topWebPages' => $this->topWebPages($historicalStart, $historicalEnd, $todayStart, $todayEnd),
            'topConsumers' => $this->topConsumers($historicalStart, $historicalEnd, $todayStart, $todayEnd),
        ];
    }

    /**
     * @return array{
     *     totalRequests: int,
     *     anonymousVisitors: int,
     *     apiRequests: int,
     *     avgResponseTime: int,
     *     chartData: array<string, int>,
     *     topApiEndpoints: Collection<int, object>,
     *     topWebPages: Collection<int, object>,
     *     topConsumers: Collection<int, object>
     * }
     */
    private function todaySnapshot(string $routeType, CarbonInterface $startDate, CarbonInterface $endDate): array
    {
        $totals = $this->rawTotals($startDate, $endDate, $routeType);

        return [
            'totalRequests' => $totals['request_count'],
            'anonymousVisitors' => $totals['unique_visitors'],
            'apiRequests' => $totals['api_requests'],
            'avgResponseTime' => $totals['duration_samples'] > 0
                ? (int) round($totals['duration_total_ms'] / $totals['duration_samples'])
                : 0,
            'chartData' => $this->hourlyChartData($startDate, $endDate, $routeType),
            'topApiEndpoints' => $this->rawTopApiEndpoints($startDate, $endDate),
            'topWebPages' => $this->rawTopWebPages($startDate, $endDate),
            'topConsumers' => $this->rawTopConsumers($startDate, $endDate),
        ];
    }

    /**
     * @return array{0: CarbonInterface|null, 1: CarbonInterface|null}
     */
    private function historicalBounds(?CarbonInterface $startDate, ?CarbonInterface $endDate): array
    {
        $yesterdayEnd = now()->subDay()->endOfDay();
        $historicalEnd = match (true) {
            $endDate === null => $yesterdayEnd,
            $endDate->gte(now()->startOfDay()) => $yesterdayEnd,
            default => $endDate->copy()->endOfDay(),
        };

        $historicalStart = $startDate?->copy()->startOfDay();

        if ($historicalStart === null) {
            $earliest = AnalyticsDailyRouteStat::query()->min('stat_date');
            $historicalStart = $earliest ? Carbon::parse($earliest)->startOfDay() : null;
        }

        if ($historicalStart === null || $historicalStart->gt($historicalEnd)) {
            return [null, null];
        }

        return [$historicalStart, $historicalEnd];
    }

    private function includesToday(?CarbonInterface $startDate, ?CarbonInterface $endDate): bool
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $startsOnOrBeforeToday = $startDate === null || $startDate->lte($todayEnd);
        $endsOnOrAfterToday = $endDate === null || $endDate->gte($todayStart);

        return $startsOnOrBeforeToday && $endsOnOrAfterToday;
    }

    /**
     * @return array{request_count: int, unique_visitors: int, api_requests: int, duration_total_ms: int, duration_samples: int}
     */
    private function historicalTotals(CarbonInterface $start, CarbonInterface $end, string $routeType): array
    {
        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $matching = AnalyticsDailyRouteStat::query()
            ->whereBetween('stat_date', [$startDate, $endDate])
            ->where('route_type', $routeType === 'all' ? 'all' : $routeType)
            ->selectRaw('COALESCE(SUM(request_count), 0) as request_count, COALESCE(SUM(unique_visitors), 0) as unique_visitors, COALESCE(SUM(duration_total_ms), 0) as duration_total_ms, COALESCE(SUM(duration_samples), 0) as duration_samples')
            ->first();

        $apiRequests = $routeType === 'web' || $routeType === 'admin'
            ? 0
            : (int) AnalyticsDailyRouteStat::query()
                ->whereBetween('stat_date', [$startDate, $endDate])
                ->where('route_type', 'api')
                ->sum('request_count');

        return [
            'request_count' => (int) ($matching->request_count ?? 0),
            'unique_visitors' => (int) ($matching->unique_visitors ?? 0),
            'api_requests' => $apiRequests,
            'duration_total_ms' => (int) ($matching->duration_total_ms ?? 0),
            'duration_samples' => (int) ($matching->duration_samples ?? 0),
        ];
    }

    /**
     * @return array{request_count: int, unique_visitors: int, api_requests: int, duration_total_ms: int, duration_samples: int}
     */
    private function rawTotals(CarbonInterface $start, CarbonInterface $end, string $routeType): array
    {
        $row = DB::table('request_logs')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->when($routeType !== 'all', fn ($query) => $query->where('route_type', $routeType))
            ->selectRaw("COUNT(*) as request_count, COUNT(DISTINCT visitor_hash) as unique_visitors, SUM(CASE WHEN route_type = 'api' THEN 1 ELSE 0 END) as api_requests, COALESCE(SUM(duration_ms), 0) as duration_total_ms, COUNT(duration_ms) as duration_samples")
            ->first();

        return [
            'request_count' => (int) ($row->request_count ?? 0),
            'unique_visitors' => (int) ($row->unique_visitors ?? 0),
            'api_requests' => (int) ($row->api_requests ?? 0),
            'duration_total_ms' => (int) ($row->duration_total_ms ?? 0),
            'duration_samples' => (int) ($row->duration_samples ?? 0),
        ];
    }

    /**
     * @return array{request_count: int, unique_visitors: int, api_requests: int, duration_total_ms: int, duration_samples: int}
     */
    private function emptyTotals(): array
    {
        return [
            'request_count' => 0,
            'unique_visitors' => 0,
            'api_requests' => 0,
            'duration_total_ms' => 0,
            'duration_samples' => 0,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function hourlyChartData(CarbonInterface $start, CarbonInterface $end, string $routeType): array
    {
        $query = DB::table('request_logs')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->when($routeType !== 'all', fn ($builder) => $builder->where('route_type', $routeType));

        $driver = DB::connection()->getDriverName();
        $logData = $driver === 'sqlite'
            ? $query->selectRaw("cast(strftime('%H', created_at) as integer) as hour_num, COUNT(*) as count")->groupBy('hour_num')->pluck('count', 'hour_num')
            : $query->selectRaw('HOUR(created_at) as hour_num, COUNT(*) as count')->groupBy('hour_num')->pluck('count', 'hour_num');

        $chartData = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $chartData[sprintf('%02d:00', $hour)] = (int) ($logData->get($hour) ?? 0);
        }

        return $chartData;
    }

    /**
     * @return array<string, int>
     */
    private function chartDataForPeriod(
        string $routeType,
        ?CarbonInterface $startDate,
        ?CarbonInterface $endDate,
        ?CarbonInterface $historicalStart,
        ?CarbonInterface $historicalEnd,
        bool $includesToday,
    ): array {
        $firstDate = $startDate?->copy()->startOfDay()
            ?? $historicalStart?->copy()->startOfDay()
            ?? now()->startOfDay();
        $lastDate = $endDate?->copy()->startOfDay()
            ?? now()->startOfDay();

        $groupByMonth = $firstDate->diffInDays($lastDate) > 31;
        $counts = [];

        if ($historicalStart && $historicalEnd) {
            $rows = AnalyticsDailyRouteStat::query()
                ->whereBetween('stat_date', [$historicalStart->toDateString(), $historicalEnd->toDateString()])
                ->where('route_type', $routeType === 'all' ? 'all' : $routeType)
                ->get(['stat_date', 'request_count']);

            foreach ($rows as $row) {
                $date = Carbon::parse($row->stat_date);
                $key = $date->format($groupByMonth ? 'Y-m' : 'Y-m-d');
                $counts[$key] = ($counts[$key] ?? 0) + (int) $row->request_count;
            }
        }

        if ($includesToday) {
            $todayTotals = $this->rawTotals(now()->startOfDay(), $endDate ?? now()->endOfDay(), $routeType);
            $todayKey = now()->format($groupByMonth ? 'Y-m' : 'Y-m-d');
            $counts[$todayKey] = ($counts[$todayKey] ?? 0) + $todayTotals['request_count'];
        }

        $chartData = [];
        $cursor = $groupByMonth ? $firstDate->copy()->startOfMonth() : $firstDate->copy()->startOfDay();
        $finalDate = $groupByMonth ? $lastDate->copy()->startOfMonth() : $lastDate->copy()->startOfDay();

        while ($cursor->lte($finalDate)) {
            $key = $cursor->format($groupByMonth ? 'Y-m' : 'Y-m-d');
            $label = $cursor->format($groupByMonth ? 'M Y' : 'M d');
            $chartData[$label] = (int) ($counts[$key] ?? 0);
            $cursor = $groupByMonth ? $cursor->addMonth() : $cursor->addDay();
        }

        return $chartData;
    }

    /**
     * @return Collection<int, object>
     */
    private function topApiEndpoints(
        ?CarbonInterface $historicalStart,
        ?CarbonInterface $historicalEnd,
        CarbonInterface $todayStart,
        ?CarbonInterface $todayEnd,
    ): Collection {
        $historical = $historicalStart && $historicalEnd
            ? AnalyticsDailyPathStat::query()
                ->where('route_type', 'api')
                ->whereBetween('stat_date', [$historicalStart->toDateString(), $historicalEnd->toDateString()])
                ->selectRaw('path, method, SUM(request_count) as count, SUM(unique_visitors) as anonymous_visitors, SUM(duration_total_ms) as duration_total_ms, SUM(duration_samples) as duration_samples')
                ->groupBy('path', 'method')
                ->orderByDesc('count')
                ->limit(5)
                ->get()
            : collect();

        $today = $todayEnd ? $this->rawTopApiEndpoints($todayStart, $todayEnd) : collect();

        return $this->mergeEndpointRows($historical, $today)
            ->sortByDesc(fn (object $row): int => (int) $row->count)
            ->take(5)
            ->values();
    }

    /**
     * @return Collection<int, object>
     */
    private function topWebPages(
        ?CarbonInterface $historicalStart,
        ?CarbonInterface $historicalEnd,
        CarbonInterface $todayStart,
        ?CarbonInterface $todayEnd,
    ): Collection {
        $historical = $historicalStart && $historicalEnd
            ? AnalyticsDailyPathStat::query()
                ->where('route_type', 'web')
                ->whereBetween('stat_date', [$historicalStart->toDateString(), $historicalEnd->toDateString()])
                ->selectRaw('path, SUM(request_count) as count, SUM(unique_visitors) as anonymous_visitors, SUM(duration_total_ms) as duration_total_ms, SUM(duration_samples) as duration_samples')
                ->groupBy('path')
                ->orderByDesc('count')
                ->limit(5)
                ->get()
            : collect();

        $today = $todayEnd ? $this->rawTopWebPages($todayStart, $todayEnd) : collect();

        return $this->mergePathRows($historical, $today)
            ->sortByDesc(fn (object $row): int => (int) $row->count)
            ->take(5)
            ->values();
    }

    /**
     * @return Collection<int, object>
     */
    private function topConsumers(
        ?CarbonInterface $historicalStart,
        ?CarbonInterface $historicalEnd,
        CarbonInterface $todayStart,
        ?CarbonInterface $todayEnd,
    ): Collection {
        $historical = $historicalStart && $historicalEnd
            ? AnalyticsDailyVisitorStat::query()
                ->whereBetween('stat_date', [$historicalStart->toDateString(), $historicalEnd->toDateString()])
                ->selectRaw('visitor_hash, SUM(request_count) as count, MAX(last_active) as last_active, MAX(user_agent) as user_agent')
                ->groupBy('visitor_hash')
                ->orderByDesc('count')
                ->limit(5)
                ->get()
            : collect();

        $today = $todayEnd ? $this->rawTopConsumers($todayStart, $todayEnd) : collect();

        $merged = collect();
        foreach ($historical->concat($today) as $row) {
            $hash = (string) $row->visitor_hash;
            if (! $merged->has($hash)) {
                $merged[$hash] = (object) [
                    'visitor_hash' => $hash,
                    'count' => 0,
                    'last_active' => $row->last_active,
                    'user_agent' => $row->user_agent,
                ];
            }

            $merged[$hash]->count += (int) $row->count;
            if ($row->last_active > $merged[$hash]->last_active) {
                $merged[$hash]->last_active = $row->last_active;
                $merged[$hash]->user_agent = $row->user_agent;
            }
        }

        return $merged
            ->sortByDesc(fn (object $row): int => (int) $row->count)
            ->take(5)
            ->values();
    }

    /**
     * @return Collection<int, object>
     */
    private function rawTopApiEndpoints(CarbonInterface $start, CarbonInterface $end): Collection
    {
        return DB::table('request_logs')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->where('route_type', 'api')
            ->selectRaw('path, method, COUNT(*) as count, COUNT(DISTINCT visitor_hash) as anonymous_visitors, COALESCE(SUM(duration_ms), 0) as duration_total_ms, COUNT(duration_ms) as duration_samples')
            ->groupBy('path', 'method')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(fn (object $row): object => $this->endpointViewRow($row));
    }

    /**
     * @return Collection<int, object>
     */
    private function rawTopWebPages(CarbonInterface $start, CarbonInterface $end): Collection
    {
        return DB::table('request_logs')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->where('route_type', 'web')
            ->selectRaw('path, COUNT(*) as count, COUNT(DISTINCT visitor_hash) as anonymous_visitors, COALESCE(SUM(duration_ms), 0) as duration_total_ms, COUNT(duration_ms) as duration_samples')
            ->groupBy('path')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(fn (object $row): object => $this->pathViewRow($row));
    }

    /**
     * @return Collection<int, object>
     */
    private function rawTopConsumers(CarbonInterface $start, CarbonInterface $end): Collection
    {
        return DB::table('request_logs')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->whereNotNull('visitor_hash')
            ->selectRaw('visitor_hash, COUNT(*) as count, MAX(created_at) as last_active, MAX(user_agent) as user_agent')
            ->groupBy('visitor_hash')
            ->orderByDesc('count')
            ->limit(5)
            ->get();
    }

    /**
     * @param  Collection<int, object>  $historical
     * @param  Collection<int, object>  $today
     * @return Collection<int, object>
     */
    private function mergeEndpointRows(Collection $historical, Collection $today): Collection
    {
        $merged = collect();

        foreach ($historical->concat($today) as $row) {
            $key = $row->method.' '.$row->path;
            if (! $merged->has($key)) {
                $merged[$key] = (object) [
                    'path' => $row->path,
                    'method' => $row->method,
                    'count' => 0,
                    'anonymous_visitors' => 0,
                    'duration_total_ms' => 0,
                    'duration_samples' => 0,
                    'avg_duration' => 0,
                ];
            }

            $merged[$key]->count += (int) $row->count;
            $merged[$key]->anonymous_visitors += (int) $row->anonymous_visitors;
            $merged[$key]->duration_total_ms += (int) ($row->duration_total_ms ?? 0);
            $merged[$key]->duration_samples += (int) ($row->duration_samples ?? 0);
            $merged[$key]->avg_duration = $merged[$key]->duration_samples > 0
                ? $merged[$key]->duration_total_ms / $merged[$key]->duration_samples
                : (float) ($row->avg_duration ?? 0);
        }

        return $merged->values();
    }

    /**
     * @param  Collection<int, object>  $historical
     * @param  Collection<int, object>  $today
     * @return Collection<int, object>
     */
    private function mergePathRows(Collection $historical, Collection $today): Collection
    {
        $merged = collect();

        foreach ($historical->concat($today) as $row) {
            $key = (string) $row->path;
            if (! $merged->has($key)) {
                $merged[$key] = (object) [
                    'path' => $row->path,
                    'count' => 0,
                    'anonymous_visitors' => 0,
                    'duration_total_ms' => 0,
                    'duration_samples' => 0,
                    'avg_duration' => 0,
                ];
            }

            $merged[$key]->count += (int) $row->count;
            $merged[$key]->anonymous_visitors += (int) $row->anonymous_visitors;
            $merged[$key]->duration_total_ms += (int) ($row->duration_total_ms ?? 0);
            $merged[$key]->duration_samples += (int) ($row->duration_samples ?? 0);
            $merged[$key]->avg_duration = $merged[$key]->duration_samples > 0
                ? $merged[$key]->duration_total_ms / $merged[$key]->duration_samples
                : (float) ($row->avg_duration ?? 0);
        }

        return $merged->values();
    }

    private function endpointViewRow(object $row): object
    {
        $samples = (int) ($row->duration_samples ?? 0);

        return (object) [
            'path' => $row->path,
            'method' => $row->method,
            'count' => (int) $row->count,
            'anonymous_visitors' => (int) $row->anonymous_visitors,
            'duration_total_ms' => (int) ($row->duration_total_ms ?? 0),
            'duration_samples' => $samples,
            'avg_duration' => $samples > 0 ? ((int) $row->duration_total_ms) / $samples : 0,
        ];
    }

    private function pathViewRow(object $row): object
    {
        $samples = (int) ($row->duration_samples ?? 0);

        return (object) [
            'path' => $row->path,
            'count' => (int) $row->count,
            'anonymous_visitors' => (int) $row->anonymous_visitors,
            'duration_total_ms' => (int) ($row->duration_total_ms ?? 0),
            'duration_samples' => $samples,
            'avg_duration' => $samples > 0 ? ((int) $row->duration_total_ms) / $samples : 0,
        ];
    }
}
