<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

test('guests are redirected away from admin sources', function () {
    $this->get('/admin/sources')
        ->assertRedirect(route('login'));
});

test('data admins can access admin source pages', function () {
    $user = User::factory()->create([
        'role' => 'data_admin',
    ]);

    $this->actingAs($user)
        ->get('/admin/sources')
        ->assertOk()
        ->assertSee('Import Sources');
});

test('data admins can access redesigned admin workflow pages', function (string $path, string $expectedText) {
    $user = User::factory()->create([
        'role' => 'data_admin',
    ]);

    $this->actingAs($user)
        ->get($path)
        ->assertOk()
        ->assertSee($expectedText);
})->with([
    ['/admin/batches', 'Import Batches'],
    ['/admin/overrides', 'Holiday Overrides'],
]);

test('admin web routes use web auth and role middleware only', function () {
    $adminRoutes = collect(Route::getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'admin/'));

    expect($adminRoutes)->not->toBeEmpty();

    $adminRoutes->each(function ($route) {
        expect($route->gatherMiddleware())
            ->toContain('auth')
            ->toContain('verified')
            ->toContain('role:super_admin,data_admin')
            ->not->toContain('auth:sanctum');
    });
});

test('admin routes are not registered under the public api namespace', function () {
    $apiAdminRoutes = collect(Route::getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1/admin'));

    expect($apiAdminRoutes)->toBeEmpty();
});
