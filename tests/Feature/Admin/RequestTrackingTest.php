<?php

use App\Livewire\Admin\AnalyticsDashboard;
use App\Models\RequestLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'analytics.enabled' => true,
        'analytics.hash_secret' => 'testing-analytics-secret',
        'analytics.store_raw_ip' => false,
        'analytics.store_network_prefix' => false,
    ]);
});

test('web requests are not tracked', function () {
    expect(RequestLog::count())->toBe(0);

    $this->get('/')->assertSuccessful();

    expect(RequestLog::count())->toBe(0);
});

test('api requests are tracked and logged as api route type', function () {
    $this->get('/api/v1/states')
        ->assertStatus(200);

    expect(RequestLog::count())->toBe(1);

    $log = RequestLog::first();
    expect($log->route_type)->toBe('api');
    expect($log->path)->toBe('/api/v1/states');
    expect($log->status_code)->toBe(200);
});

test('api not found requests are tracked', function () {
    $this->getJson('/api/v1/does-not-exist')
        ->assertNotFound();

    expect(RequestLog::count())->toBe(1);
    expect(RequestLog::first()->route_type)->toBe('api');
    expect(RequestLog::first()->status_code)->toBe(404);
});

test('non-api pages and non-200 api responses are not tracked', function () {
    $this->get('/api/docs')->assertSuccessful();
    $this->getJson('/api/v1/holidays?year=not-a-year')->assertUnprocessable();

    expect(RequestLog::count())->toBe(0);
});

test('admin requests are not tracked', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get('/admin/sources')
        ->assertStatus(200);

    expect(RequestLog::count())->toBe(0);
});

test('excluded paths and assets are not tracked', function () {
    $this->get('/livewire/livewire.js');
    $this->get('/css/app.css');
    $this->get('/up');

    expect(RequestLog::count())->toBe(0);
});

test('guests are redirected away from analytics dashboard', function () {
    $this->get('/admin/analytics')
        ->assertRedirect(route('login'));
});

test('admins can view the analytics dashboard and interact with it', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    RequestLog::create([
        'visitor_hash' => str_repeat('a', 64),
        'method' => 'GET',
        'path' => '/',
        'full_url' => '/',
        'status_code' => 200,
        'user_agent' => 'Symfony',
        'duration_ms' => 50,
        'route_type' => 'web',
    ]);

    RequestLog::create([
        'visitor_hash' => str_repeat('b', 64),
        'method' => 'GET',
        'path' => '/api/v1/states',
        'full_url' => '/api/v1/states',
        'status_code' => 200,
        'user_agent' => 'Guzzle',
        'duration_ms' => 120,
        'route_type' => 'api',
    ]);

    $this->actingAs($admin)
        ->get('/admin/analytics')
        ->assertStatus(200);

    Livewire::actingAs($admin)
        ->test(AnalyticsDashboard::class)
        ->assertSee('Application Analytics')
        ->assertSee('Visitor aaaaaaaa')
        ->assertSee('Visitor bbbbbbbb')
        ->assertSee('/api/v1/states')
        ->set('timeframe', 'today')
        ->assertSet('timeframe', 'today')
        ->set('routeType', 'api')
        ->assertSet('routeType', 'api')
        ->set('search', '/api/v1/states')
        ->assertSee('/api/v1/states');
});
