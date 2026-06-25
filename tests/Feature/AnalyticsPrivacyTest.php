<?php

use App\Livewire\Admin\AnalyticsDashboard;
use App\Models\AuditLog;
use App\Models\RequestLog;
use App\Models\User;
use App\Services\Analytics\VisitorIdentifier;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

beforeEach(function () {
    config([
        'analytics.enabled' => true,
        'analytics.hash_secret' => 'testing-analytics-secret',
        'analytics.retention_days' => 90,
        'analytics.store_raw_ip' => false,
        'analytics.store_network_prefix' => false,
        'analytics.audit_log_pruning_enabled' => false,
        'analytics.audit_log_retention_days' => 365,
    ]);
});

test('new analytics rows do not store raw ip by default and preserve only allowlisted query parameters', function () {
    $this->get('/api/docs?utm_source=docs&utm_medium=web&token=secret&foo=bar')
        ->assertSuccessful();

    $log = RequestLog::query()->firstOrFail();

    expect($log->ip_address)->toBeNull()
        ->and($log->visitor_hash)->toHaveLength(64)
        ->and($log->network_prefix)->toBeNull()
        ->and($log->path)->toBe('/api/docs')
        ->and($log->full_url)->toBe('/api/docs?utm_source=docs&utm_medium=web')
        ->and($log->expires_at)->not->toBeNull();
});

test('raw analytics ip is stored only when explicitly enabled', function () {
    config(['analytics.store_raw_ip' => true]);

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
        ->get('/api/v1/states')
        ->assertSuccessful();

    expect(RequestLog::query()->firstOrFail()->ip_address)->toBe('198.51.100.10');
});

test('missing analytics secret skips logging without breaking the response', function () {
    config(['analytics.hash_secret' => '']);
    Log::spy();

    $this->get('/')
        ->assertSuccessful();

    expect(RequestLog::query()->count())->toBe(0);
    Log::shouldHaveReceived('warning')->once();
});

test('spoofed forwarded headers are ignored when no trusted proxy is configured', function () {
    $this->withServerVariables([
        'REMOTE_ADDR' => '198.51.100.10',
        'HTTP_X_FORWARDED_FOR' => '203.0.113.99',
    ])->get('/api/v1/states')->assertSuccessful();

    $expectedHash = app(VisitorIdentifier::class)->hash('198.51.100.10');

    expect(RequestLog::query()->firstOrFail()->visitor_hash)->toBe($expectedHash);
});

test('analytics dashboard livewire polling is not tracked while other livewire requests are not broadly excluded', function () {
    $this->post('/livewire/update', [
        'components' => [
            [
                'snapshot' => json_encode(['memo' => ['name' => 'admin.analytics-dashboard']]),
                'updates' => [],
                'calls' => [],
            ],
        ],
    ]);

    expect(RequestLog::query()->count())->toBe(0);

    $this->post('/livewire/update', [
        'components' => [
            [
                'snapshot' => json_encode(['memo' => ['name' => 'some.other-component']]),
                'updates' => [],
                'calls' => [],
            ],
        ],
    ]);

    expect(RequestLog::query()->count())->toBe(1);
});

test('analytics prune deletes expired request logs and keeps non expired rows in chunks', function () {
    RequestLog::query()->create([
        'visitor_hash' => str_repeat('a', 64),
        'method' => 'GET',
        'path' => '/expired-one',
        'full_url' => '/expired-one',
        'status_code' => 200,
        'route_type' => 'web',
        'expires_at' => now()->subDay(),
    ]);
    RequestLog::query()->create([
        'visitor_hash' => str_repeat('b', 64),
        'method' => 'GET',
        'path' => '/expired-two',
        'full_url' => '/expired-two',
        'status_code' => 200,
        'route_type' => 'web',
        'expires_at' => now()->subHour(),
    ]);
    RequestLog::query()->create([
        'visitor_hash' => str_repeat('c', 64),
        'method' => 'GET',
        'path' => '/kept',
        'full_url' => '/kept',
        'status_code' => 200,
        'route_type' => 'web',
        'expires_at' => now()->addDay(),
    ]);

    $this->artisan('analytics:prune', ['--chunk' => 1])
        ->expectsOutput('Request logs deleted: 2')
        ->expectsOutput('Audit log pruning is disabled.')
        ->assertSuccessful();

    expect(RequestLog::query()->pluck('path')->all())->toBe(['/kept']);
});

test('analytics prune dry run does not delete records', function () {
    RequestLog::query()->create([
        'visitor_hash' => str_repeat('a', 64),
        'method' => 'GET',
        'path' => '/expired',
        'full_url' => '/expired',
        'status_code' => 200,
        'route_type' => 'web',
        'expires_at' => now()->subDay(),
    ]);

    $this->artisan('analytics:prune', ['--dry-run' => true])
        ->expectsOutput('Request logs that would be deleted: 1')
        ->assertSuccessful();

    expect(RequestLog::query()->count())->toBe(1);
});

test('audit logs are not pruned unless explicitly enabled', function () {
    $auditLog = AuditLog::query()->create([
        'action' => 'source_uploaded',
        'entity_type' => 'holiday_source',
        'entity_id' => 1,
        'ip_address' => '198.51.100.10',
        'user_agent' => 'Pest',
        'created_at' => now()->subYears(2),
    ]);

    $this->artisan('analytics:prune')
        ->expectsOutput('Audit log pruning is disabled.')
        ->assertSuccessful();

    expect(AuditLog::query()->whereKey($auditLog)->exists())->toBeTrue();
});

test('historical request ip cleanup requires before and force and only affects request logs before cutoff', function () {
    $oldLog = RequestLog::query()->create([
        'ip_address' => '198.51.100.10',
        'method' => 'GET',
        'path' => '/old',
        'full_url' => '/old',
        'status_code' => 200,
        'route_type' => 'web',
    ]);
    $oldLog->forceFill(['created_at' => '2026-01-01 00:00:00'])->save();

    $newLog = RequestLog::query()->create([
        'ip_address' => '198.51.100.11',
        'method' => 'GET',
        'path' => '/new',
        'full_url' => '/new',
        'status_code' => 200,
        'route_type' => 'web',
    ]);
    $newLog->forceFill(['created_at' => '2026-06-01 00:00:00'])->save();

    $this->artisan('analytics:anonymize-request-ips')
        ->expectsOutput('The --before=YYYY-MM-DD option is required.')
        ->assertFailed();

    $this->artisan('analytics:anonymize-request-ips', ['--before' => '2026-03-01', '--dry-run' => true])
        ->expectsOutput('Request log IP addresses that would be nulled: 1')
        ->assertSuccessful();

    expect($oldLog->fresh()->ip_address)->toBe('198.51.100.10');

    $this->artisan('analytics:anonymize-request-ips', ['--before' => '2026-03-01'])
        ->expectsOutput('Refusing to modify records without --force. Use --dry-run to preview affected rows.')
        ->assertFailed();

    $this->artisan('analytics:anonymize-request-ips', ['--before' => '2026-03-01', '--force' => true, '--chunk' => 1])
        ->expectsOutput('Request log IP addresses nulled: 1')
        ->assertSuccessful();

    expect($oldLog->fresh()->ip_address)->toBeNull()
        ->and($newLog->fresh()->ip_address)->toBe('198.51.100.11');
});

test('admin analytics dashboard does not display raw analytics ip addresses', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    RequestLog::query()->create([
        'ip_address' => '198.51.100.10',
        'visitor_hash' => str_repeat('d', 64),
        'method' => 'GET',
        'path' => '/api/v1/states',
        'full_url' => '/api/v1/states',
        'status_code' => 200,
        'user_agent' => 'Pest',
        'duration_ms' => 50,
        'route_type' => 'api',
    ]);

    Livewire::actingAs($admin)
        ->test(AnalyticsDashboard::class)
        ->assertSee('Visitor dddddddd')
        ->assertDontSee('198.51.100.10');
});
