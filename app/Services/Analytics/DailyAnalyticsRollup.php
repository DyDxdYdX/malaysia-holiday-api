<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsDailyPathStat;
use App\Models\AnalyticsDailyRouteStat;
use App\Models\AnalyticsDailyVisitorStat;
use App\Models\RequestLog;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DailyAnalyticsRollup
{
    /**
     * @return Collection<int, CarbonInterface>
     */
    public function datesToProcess(?string $from, ?string $to, int $limit, bool $force): Collection
    {
        $rangeEnd = $this->parseDate($to) ?? now()->subDay()->startOfDay();
        $rangeStart = $this->parseDate($from) ?? $this->earliestLogDate() ?? $rangeEnd->copy();

        if ($rangeStart->gt($rangeEnd)) {
            return collect();
        }

        $existing = AnalyticsDailyRouteStat::query()
            ->where('route_type', 'all')
            ->whereBetween('stat_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->pluck('stat_date')
            ->map(fn (mixed $date): string => Carbon::parse($date)->toDateString())
            ->all();

        $yesterday = now()->subDay()->toDateString();
        $dates = [];
        $cursor = $rangeEnd->copy()->startOfDay();
        $rangeStart = $rangeStart->copy()->startOfDay();

        while ($cursor->gte($rangeStart)) {
            $key = $cursor->toDateString();
            $alreadyRolledUp = in_array($key, $existing, true);
            $isYesterday = $key === $yesterday;

            if ($force || $isYesterday || (! $alreadyRolledUp && $this->hasLogsOn($cursor))) {
                $dates[] = $key;

                if ($limit > 0 && count($dates) >= $limit) {
                    break;
                }
            }

            $cursor = $cursor->copy()->subDay();
        }

        return collect($dates)
            ->unique()
            ->values()
            ->map(fn (string $date): CarbonInterface => Carbon::parse($date)->startOfDay());
    }

    public function rollupDay(CarbonInterface $day, int $chunkSize = 500): void
    {
        $start = $day->copy()->startOfDay();
        $end = $day->copy()->endOfDay();
        $statDate = $start->toDateString();
        $chunkSize = max(1, $chunkSize);

        DB::transaction(function () use ($start, $end, $statDate, $chunkSize): void {
            AnalyticsDailyRouteStat::query()->where('stat_date', $statDate)->delete();
            AnalyticsDailyPathStat::query()->where('stat_date', $statDate)->delete();
            AnalyticsDailyVisitorStat::query()->where('stat_date', $statDate)->delete();

            $dayLogs = DB::table('request_logs')
                ->where('created_at', '>=', $start)
                ->where('created_at', '<=', $end);

            $totals = (clone $dayLogs)
                ->selectRaw('COUNT(*) as request_count, COUNT(DISTINCT visitor_hash) as unique_visitors, COALESCE(SUM(duration_ms), 0) as duration_total_ms, COUNT(duration_ms) as duration_samples')
                ->first();

            $requestCount = (int) ($totals->request_count ?? 0);

            $routeRows = [
                $this->routeStatRow($statDate, 'all', $requestCount, (int) ($totals->unique_visitors ?? 0), (int) ($totals->duration_total_ms ?? 0), (int) ($totals->duration_samples ?? 0)),
            ];

            if ($requestCount > 0) {
                $byRoute = (clone $dayLogs)
                    ->selectRaw('route_type, COUNT(*) as request_count, COUNT(DISTINCT visitor_hash) as unique_visitors, COALESCE(SUM(duration_ms), 0) as duration_total_ms, COUNT(duration_ms) as duration_samples')
                    ->groupBy('route_type')
                    ->get();

                foreach ($byRoute as $row) {
                    $routeRows[] = $this->routeStatRow(
                        $statDate,
                        (string) $row->route_type,
                        (int) $row->request_count,
                        (int) $row->unique_visitors,
                        (int) $row->duration_total_ms,
                        (int) $row->duration_samples,
                    );
                }
            }

            AnalyticsDailyRouteStat::query()->insert($routeRows);

            if ($requestCount === 0) {
                return;
            }

            $pathRows = (clone $dayLogs)
                ->selectRaw('route_type, method, path, COUNT(*) as request_count, COUNT(DISTINCT visitor_hash) as unique_visitors, COALESCE(SUM(duration_ms), 0) as duration_total_ms, COUNT(duration_ms) as duration_samples')
                ->groupBy('route_type', 'method', 'path')
                ->get()
                ->map(fn (object $row): array => [
                    'stat_date' => $statDate,
                    'route_type' => (string) $row->route_type,
                    'method' => (string) $row->method,
                    'path' => (string) $row->path,
                    'request_count' => (int) $row->request_count,
                    'unique_visitors' => (int) $row->unique_visitors,
                    'duration_total_ms' => (int) $row->duration_total_ms,
                    'duration_samples' => (int) $row->duration_samples,
                ]);

            foreach ($pathRows->chunk($chunkSize) as $chunk) {
                AnalyticsDailyPathStat::query()->insert($chunk->values()->all());
            }

            $visitorRows = (clone $dayLogs)
                ->whereNotNull('visitor_hash')
                ->selectRaw('visitor_hash, COUNT(*) as request_count, MAX(created_at) as last_active, MAX(user_agent) as user_agent')
                ->groupBy('visitor_hash')
                ->get()
                ->map(fn (object $row): array => [
                    'stat_date' => $statDate,
                    'visitor_hash' => (string) $row->visitor_hash,
                    'request_count' => (int) $row->request_count,
                    'last_active' => $row->last_active,
                    'user_agent' => $row->user_agent,
                ]);

            foreach ($visitorRows->chunk($chunkSize) as $chunk) {
                AnalyticsDailyVisitorStat::query()->insert($chunk->values()->all());
            }
        });
    }

    private function earliestLogDate(): ?CarbonInterface
    {
        $earliest = RequestLog::query()->min('created_at');

        return $earliest ? Carbon::parse($earliest)->startOfDay() : null;
    }

    private function hasLogsOn(CarbonInterface $day): bool
    {
        return RequestLog::query()
            ->where('created_at', '>=', $day->copy()->startOfDay())
            ->where('created_at', '<=', $day->copy()->endOfDay())
            ->exists();
    }

    private function parseDate(?string $value): ?CarbonInterface
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            throw new InvalidArgumentException('Dates must use YYYY-MM-DD format.');
        }
    }

    /**
     * @return array{stat_date: string, route_type: string, request_count: int, unique_visitors: int, duration_total_ms: int, duration_samples: int}
     */
    private function routeStatRow(string $statDate, string $routeType, int $requestCount, int $uniqueVisitors, int $durationTotalMs, int $durationSamples): array
    {
        return [
            'stat_date' => $statDate,
            'route_type' => $routeType,
            'request_count' => $requestCount,
            'unique_visitors' => $uniqueVisitors,
            'duration_total_ms' => $durationTotalMs,
            'duration_samples' => $durationSamples,
        ];
    }
}
