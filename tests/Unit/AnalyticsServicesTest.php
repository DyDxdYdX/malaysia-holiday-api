<?php

use App\Services\Analytics\UrlSanitizer;
use App\Services\Analytics\VisitorIdentifier;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    config([
        'analytics.hash_secret' => 'testing-analytics-secret',
        'analytics.hash_rotation' => 'daily',
        'analytics.store_network_prefix' => false,
        'analytics.allowed_query_parameters' => [
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_term',
            'utm_content',
        ],
    ]);
});

test('same ip produces the same hash during the same utc day', function () {
    $identifier = app(VisitorIdentifier::class);
    $now = Carbon::parse('2026-06-25 12:00:00', 'UTC');

    expect($identifier->hash('203.0.113.10', $now))
        ->toBe($identifier->hash('203.0.113.10', $now->copy()->addHours(3)))
        ->toHaveLength(64);
});

test('same ip produces a different hash on the next utc day', function () {
    $identifier = app(VisitorIdentifier::class);

    expect($identifier->hash('203.0.113.10', Carbon::parse('2026-06-25 23:59:59', 'UTC')))
        ->not->toBe($identifier->hash('203.0.113.10', Carbon::parse('2026-06-26 00:00:00', 'UTC')));
});

test('different ips produce different hashes', function () {
    $identifier = app(VisitorIdentifier::class);
    $now = Carbon::parse('2026-06-25 12:00:00', 'UTC');

    expect($identifier->hash('203.0.113.10', $now))
        ->not->toBe($identifier->hash('203.0.113.11', $now));
});

test('missing analytics secret skips hash and emits one rate limited warning', function () {
    config(['analytics.hash_secret' => '']);
    Cache::forget('analytics:missing-hash-secret-warning');
    Log::spy();

    $identifier = app(VisitorIdentifier::class);

    expect($identifier->hash('203.0.113.10'))->toBeNull()
        ->and($identifier->hash('203.0.113.10'))->toBeNull();

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('Analytics request logging skipped because ANALYTICS_HASH_SECRET is not configured.');
});

test('network prefix is not populated unless explicitly enabled', function () {
    $identifier = app(VisitorIdentifier::class);

    expect($identifier->networkPrefix('203.0.113.10'))->toBeNull();

    config(['analytics.store_network_prefix' => true]);

    expect($identifier->networkPrefix('203.0.113.10'))->toBe('203.0.113.0/24');
});

test('url sanitizer preserves only allowlisted utm parameters', function () {
    $request = Request::create('/api/docs?utm_source=Google&utm_campaign=Launch&token=secret&foo=bar&email=a@example.com');

    expect(app(UrlSanitizer::class)->sanitizedUrl($request))
        ->toBe('/api/docs?utm_source=Google&utm_campaign=Launch');
});
