<?php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

test('public api endpoints are rate limited by client ip', function () {
    RateLimiter::for('public-api', function (Request $request) {
        return Limit::perMinute(2)->by($request->ip());
    });

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
        ->getJson('/api/v1/states')
        ->assertSuccessful();

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
        ->getJson('/api/v1/holidays?year=2026')
        ->assertSuccessful();

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
        ->getJson('/api/v1/states')
        ->assertTooManyRequests()
        ->assertHeader('Retry-After')
        ->assertJsonPath('error.code', 'TOO_MANY_REQUESTS')
        ->assertJsonPath('error.message', 'Too many requests.');

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.11'])
        ->getJson('/api/v1/states')
        ->assertSuccessful();
});
