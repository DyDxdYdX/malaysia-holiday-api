<?php

use App\Livewire\Admin\AnalyticsDashboard;
use App\Models\AnalyticsDailyPathStat;
use App\Models\AnalyticsDailyRouteStat;
use App\Models\AnalyticsDailyVisitorStat;
use App\Models\RequestLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    Carbon::setTestNow('2026-08-27 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

test('analytics rollup stores daily route path and visitor aggregates', function () {
    createAnalyticsLog([
        'visitor_hash' => str_repeat('a', 64),
        'path' => '/api/v1/holidays',
        'route_type' => 'api',
        'duration_ms' => 10,
    ], '2026-08-26 08:00:00');
    createAnalyticsLog([
        'visitor_hash' => str_repeat('a', 64),
        'path' => '/api/v1/holidays',
        'route_type' => 'api',
        'duration_ms' => 30,
    ], '2026-08-26 09:00:00');
    createAnalyticsLog([
        'visitor_hash' => str_repeat('b', 64),
        'path' => '/',
        'route_type' => 'web',
        'duration_ms' => 40,
    ], '2026-08-26 10:00:00');

    $this->artisan('analytics:rollup', [
        '--from' => '2026-08-26',
        '--to' => '2026-08-26',
        '--limit' => 0,
    ])
        ->expectsOutput('Daily analytics days rolled up: 1')
        ->assertSuccessful();

    $all = AnalyticsDailyRouteStat::query()
        ->where('stat_date', '2026-08-26')
        ->where('route_type', 'all')
        ->first();

    expect($all)->not->toBeNull()
        ->and($all->request_count)->toBe(3)
        ->and($all->unique_visitors)->toBe(2)
        ->and($all->duration_total_ms)->toBe(80)
        ->and($all->duration_samples)->toBe(3);

    expect(AnalyticsDailyPathStat::query()->where('stat_date', '2026-08-26')->count())->toBe(2)
        ->and(AnalyticsDailyVisitorStat::query()->where('stat_date', '2026-08-26')->count())->toBe(2);
});

test('analytics rollup is idempotent for the same day', function () {
    createAnalyticsLog([
        'visitor_hash' => str_repeat('a', 64),
        'path' => '/api/v1/holidays',
        'route_type' => 'api',
        'duration_ms' => 20,
    ], '2026-08-26 08:00:00');

    $this->artisan('analytics:rollup', ['--from' => '2026-08-26', '--to' => '2026-08-26', '--limit' => 0, '--force' => true])
        ->assertSuccessful();
    $this->artisan('analytics:rollup', ['--from' => '2026-08-26', '--to' => '2026-08-26', '--limit' => 0, '--force' => true])
        ->assertSuccessful();

    expect(AnalyticsDailyRouteStat::query()->where('stat_date', '2026-08-26')->where('route_type', 'all')->count())->toBe(1)
        ->and(AnalyticsDailyRouteStat::query()->where('stat_date', '2026-08-26')->where('route_type', 'all')->value('request_count'))->toBe(1);
});

test('analytics rollup dry run does not write summary rows', function () {
    createAnalyticsLog([
        'visitor_hash' => str_repeat('a', 64),
        'path' => '/api/v1/holidays',
        'route_type' => 'api',
        'duration_ms' => 20,
    ], '2026-08-26 08:00:00');

    $this->artisan('analytics:rollup', [
        '--from' => '2026-08-26',
        '--to' => '2026-08-26',
        '--dry-run' => true,
    ])
        ->expectsOutput('Days that would be rolled up: 1')
        ->assertSuccessful();

    expect(AnalyticsDailyRouteStat::query()->count())->toBe(0);
});

test('analytics rollup limit processes newest matching days first', function () {
    createAnalyticsLog(['visitor_hash' => str_repeat('a', 64), 'path' => '/one'], '2026-08-24 12:00:00');
    createAnalyticsLog(['visitor_hash' => str_repeat('b', 64), 'path' => '/two'], '2026-08-25 12:00:00');

    $this->artisan('analytics:rollup', [
        '--from' => '2026-08-24',
        '--to' => '2026-08-25',
        '--limit' => 1,
    ])->assertSuccessful();

    expect(
        AnalyticsDailyRouteStat::query()
            ->where('route_type', 'all')
            ->get()
            ->map(fn (AnalyticsDailyRouteStat $row): string => $row->stat_date->toDateString())
            ->all()
    )->toBe(['2026-08-25']);
});

test('analytics rollup does not queue the same day twice', function () {
    createAnalyticsLog(['visitor_hash' => str_repeat('a', 64), 'path' => '/one'], '2026-08-26 12:00:00');
    createAnalyticsLog(['visitor_hash' => str_repeat('b', 64), 'path' => '/two'], '2026-08-25 12:00:00');

    $this->artisan('analytics:rollup', ['--limit' => 2, '--dry-run' => true])
        ->expectsOutput('Days that would be rolled up: 2')
        ->expectsOutput(' 2026-08-26')
        ->expectsOutput(' 2026-08-25')
        ->assertSuccessful();
});

test('analytics rollup rejects invalid dates', function () {
    $this->artisan('analytics:rollup', ['--from' => '26-08-2026'])
        ->expectsOutput('Dates must use YYYY-MM-DD format.')
        ->assertFailed();
});

test('analytics dashboard combines rolled up history with today raw logs', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    createAnalyticsLog([
        'visitor_hash' => str_repeat('a', 64),
        'path' => '/api/v1/holidays',
        'route_type' => 'api',
        'duration_ms' => 10,
    ], '2026-08-26 08:00:00');
    createAnalyticsLog([
        'visitor_hash' => str_repeat('a', 64),
        'path' => '/api/v1/holidays',
        'route_type' => 'api',
        'duration_ms' => 30,
    ], '2026-08-26 09:00:00');
    createAnalyticsLog([
        'visitor_hash' => str_repeat('c', 64),
        'path' => '/api/v1/holidays',
        'route_type' => 'api',
        'duration_ms' => 20,
    ], '2026-08-27 09:00:00');

    $this->artisan('analytics:rollup', ['--from' => '2026-08-26', '--to' => '2026-08-26', '--limit' => 0])
        ->assertSuccessful();

    Livewire::actingAs($admin)
        ->test(AnalyticsDashboard::class)
        ->assertViewHas('totalRequests', 3)
        ->assertViewHas('anonymousVisitors', 2)
        ->assertViewHas('apiRequests', 3)
        ->assertViewHas('avgResponseTime', 20)
        ->assertDontSee('wire:poll')
        ->assertSee('Request Stream')
        ->assertDontSee('Live Request Stream');
});

test('analytics dashboard today filter reads raw logs without rollup', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    createAnalyticsLog([
        'visitor_hash' => str_repeat('d', 64),
        'path' => '/api/v1/states',
        'route_type' => 'api',
        'duration_ms' => 15,
    ], '2026-08-27 11:00:00');

    Livewire::actingAs($admin)
        ->test(AnalyticsDashboard::class)
        ->set('timeframe', 'today')
        ->assertViewHas('totalRequests', 1)
        ->assertSee('/api/v1/states');
});

test('analytics prune deletes daily stats older than retention', function () {
    AnalyticsDailyRouteStat::query()->create([
        'stat_date' => '2026-01-01',
        'route_type' => 'all',
        'request_count' => 10,
        'unique_visitors' => 4,
        'duration_total_ms' => 100,
        'duration_samples' => 10,
    ]);
    AnalyticsDailyRouteStat::query()->create([
        'stat_date' => '2026-08-20',
        'route_type' => 'all',
        'request_count' => 5,
        'unique_visitors' => 2,
        'duration_total_ms' => 50,
        'duration_samples' => 5,
    ]);

    config(['analytics.retention_days' => 90]);

    $this->artisan('analytics:prune')
        ->expectsOutput('Daily analytics stats deleted: 1')
        ->assertSuccessful();

    expect(
        AnalyticsDailyRouteStat::query()
            ->get()
            ->map(fn (AnalyticsDailyRouteStat $row): string => $row->stat_date->toDateString())
            ->all()
    )->toBe(['2026-08-20']);
});

/**
 * @param  array<string, mixed>  $overrides
 */
function createAnalyticsLog(array $overrides, string $createdAt): RequestLog
{
    $log = RequestLog::query()->create([
        'visitor_hash' => str_repeat('a', 64),
        'method' => 'GET',
        'path' => '/api/v1/holidays',
        'full_url' => '/api/v1/holidays',
        'status_code' => 200,
        'user_agent' => 'Pest',
        'duration_ms' => 20,
        'route_type' => 'api',
        ...$overrides,
    ]);

    $log->forceFill(['created_at' => $createdAt])->save();

    return $log->refresh();
}
