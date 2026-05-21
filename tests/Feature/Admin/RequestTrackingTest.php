<?php

use App\Livewire\Admin\AnalyticsDashboard;
use App\Models\RequestLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('web requests are tracked and logged as web route type', function () {
    expect(RequestLog::count())->toBe(0);

    $this->get('/')
        ->assertStatus(200);

    expect(RequestLog::count())->toBe(1);

    $log = RequestLog::first();
    expect($log->route_type)->toBe('web');
    expect($log->path)->toBe('/');
    expect($log->method)->toBe('GET');
    expect($log->status_code)->toBe(200);
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

test('admin requests are tracked with logged-in user id', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get('/admin/sources')
        ->assertStatus(200);

    $log = RequestLog::where('route_type', 'admin')->first();
    expect($log)->not->toBeNull();
    expect($log->path)->toBe('/admin/sources');
    expect($log->user_id)->toBe($admin->id);
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
        'ip_address' => '127.0.0.1',
        'method' => 'GET',
        'path' => '/',
        'full_url' => 'http://localhost/',
        'status_code' => 200,
        'user_agent' => 'Symfony',
        'duration_ms' => 50,
        'route_type' => 'web',
    ]);

    RequestLog::create([
        'ip_address' => '192.168.1.1',
        'method' => 'GET',
        'path' => '/api/v1/states',
        'full_url' => 'http://localhost/api/v1/states',
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
        ->assertSee('127.0.0.1')
        ->assertSee('192.168.1.1')
        ->assertSee('/api/v1/states')
        ->set('timeframe', 'today')
        ->assertSet('timeframe', 'today')
        ->set('routeType', 'api')
        ->assertSet('routeType', 'api')
        ->set('search', '192.168')
        ->assertSee('192.168.1.1');
});
