<?php

use App\Models\ApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function activeApiKeyForErrors(): string
{
    $rawKey = 'error-format-api-key';

    ApiClient::query()->create([
        'name' => 'Error Format Client',
        'api_key_hash' => hash('sha256', $rawKey),
        'status' => 'active',
        'rate_limit_per_minute' => 100,
    ]);

    return $rawKey;
}

test('api validation errors use standard error envelope', function () {
    $rawKey = activeApiKeyForErrors();

    $this->withHeaders(['X-API-Key' => $rawKey])
        ->getJson('/api/v1/holidays')
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.message', 'The given data was invalid.');
});

test('api unauthorized errors use standard error envelope', function () {
    $this->getJson('/api/v1/holidays?year=2026')
        ->assertStatus(401)
        ->assertJsonPath('error.code', 'UNAUTHORIZED');
});

test('api too many requests errors use standard error envelope', function () {
    $rawKey = 'error-rate-limit-key';

    ApiClient::query()->create([
        'name' => 'Rate Limited',
        'api_key_hash' => hash('sha256', $rawKey),
        'status' => 'active',
        'rate_limit_per_minute' => 1,
    ]);

    $this->withHeaders(['X-API-Key' => $rawKey])
        ->getJson('/api/v1/holidays?year=2026')
        ->assertStatus(200);

    $this->withHeaders(['X-API-Key' => $rawKey])
        ->getJson('/api/v1/holidays?year=2026')
        ->assertStatus(429)
        ->assertJsonPath('error.code', 'TOO_MANY_REQUESTS');
});

test('api not found errors use standard error envelope', function () {
    $this->getJson('/api/v1/unknown-endpoint')
        ->assertStatus(404)
        ->assertJsonPath('error.code', 'NOT_FOUND');
});
