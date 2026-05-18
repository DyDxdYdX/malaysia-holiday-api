<?php

use App\Models\ApiClient;
use App\Models\AuditLog;
use App\Models\Holiday;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedPublishedHoliday(): void
{
    Holiday::query()->create([
        'year' => 2026,
        'state_code' => 'KUL',
        'name' => 'Hari Kebangsaan',
        'date' => '2026-08-31',
        'day_name' => 'Monday',
        'scope' => 'federal',
        'type' => 'federal',
        'status' => 'published',
    ]);
}

test('private holiday endpoints require api key', function () {
    seedPublishedHoliday();

    $this->getJson('/api/v1/holidays?year=2026')
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'UNAUTHORIZED');
});

test('active api client key can access private holiday endpoints', function () {
    seedPublishedHoliday();

    $rawKey = 'valid-private-key';
    ApiClient::query()->create([
        'name' => 'Integration Client',
        'api_key_hash' => hash('sha256', $rawKey),
        'status' => 'active',
        'rate_limit_per_minute' => 10,
    ]);

    $this->withHeaders(['X-API-Key' => $rawKey])
        ->getJson('/api/v1/holidays?year=2026')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('disabled api client key is rejected', function () {
    seedPublishedHoliday();

    $rawKey = 'disabled-private-key';
    ApiClient::query()->create([
        'name' => 'Disabled Client',
        'api_key_hash' => hash('sha256', $rawKey),
        'status' => 'disabled',
        'rate_limit_per_minute' => 10,
    ]);

    $this->withHeaders(['X-API-Key' => $rawKey])
        ->getJson('/api/v1/holidays?year=2026')
        ->assertUnauthorized();
});

test('rate limiting applies per api client', function () {
    seedPublishedHoliday();

    $rawKey = 'rate-limit-private-key';
    ApiClient::query()->create([
        'name' => 'Rate Limited Client',
        'api_key_hash' => hash('sha256', $rawKey),
        'status' => 'active',
        'rate_limit_per_minute' => 2,
    ]);

    $this->withHeaders(['X-API-Key' => $rawKey])
        ->getJson('/api/v1/holidays?year=2026')
        ->assertOk();

    $this->withHeaders(['X-API-Key' => $rawKey])
        ->getJson('/api/v1/holidays?year=2026')
        ->assertOk();

    $this->withHeaders(['X-API-Key' => $rawKey])
        ->getJson('/api/v1/holidays?year=2026')
        ->assertStatus(429)
        ->assertJsonPath('error.code', 'TOO_MANY_REQUESTS');
});

test('data admin can create and disable api client with audit logs', function () {
    $user = User::factory()->create([
        'role' => 'data_admin',
    ]);

    $response = $this->actingAs($user)
        ->post(route('admin.api-clients.store'), [
            'name' => 'Vendor Client',
            'rate_limit_per_minute' => 75,
            'status' => 'active',
        ]);

    $response->assertRedirect(route('admin.api-clients.index'));

    $client = ApiClient::query()->where('name', 'Vendor Client')->firstOrFail();

    expect(AuditLog::query()->where('action', 'api_client_created')->exists())->toBeTrue();

    $this->actingAs($user)
        ->patch(route('admin.api-clients.disable', $client))
        ->assertRedirect(route('admin.api-clients.index'));

    expect($client->fresh()->status)->toBe('disabled')
        ->and(AuditLog::query()->where('action', 'api_client_disabled')->exists())->toBeTrue();
});
