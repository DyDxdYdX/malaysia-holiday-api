<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public pages include canonical and social seo metadata', function () {
    $response = $this->get(route('api.docs'));

    $response->assertOk()
        ->assertSee('<link rel="canonical" href="'.route('api.docs').'">', false)
        ->assertSee('<meta property="og:title" content="API Documentation - '.config('app.name', 'Malaysia Holiday API').'">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
        ->assertSee('<meta name="robots" content="index, follow">', false);
});

test('calendar page contains dynamic seo metadata based on year and state filters', function () {
    $currentYear = now()->year;

    // 1. Without filters (defaults to current year)
    $response = $this->get(route('holidays.calendar'));
    $response->assertOk()
        ->assertSee('<title>Malaysia Public Holidays '.$currentYear.' Calendar - '.config('app.name', 'Malaysia Holiday API').'</title>', false)
        ->assertSee('<meta name="description" content="Browse national public holidays, state-level holidays, and federal holidays in Malaysia for '.$currentYear.'.">', false)
        ->assertSee('<link rel="canonical" href="'.e(route('holidays.calendar', ['year' => $currentYear])).'">', false);

    // 2. With filters (specific year and state)
    $responseWithFilters = $this->get(route('holidays.calendar', ['year' => 2026, 'state_code' => 'SBH']));
    $responseWithFilters->assertOk()
        ->assertSee('<title>Sabah Public Holidays 2026 Calendar - '.config('app.name', 'Malaysia Holiday API').'</title>', false)
        ->assertSee('<meta name="description" content="Browse public holidays, state holidays, and long weekends in Sabah, Malaysia for 2026.">', false)
        ->assertSee('<link rel="canonical" href="'.e(route('holidays.calendar', ['year' => 2026, 'state_code' => 'SBH'])).'">', false);
});

test('sitemap endpoint exposes crawlable public urls', function () {
    $response = $this->get(route('sitemap'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee('<?xml version="1.0" encoding="UTF-8"?>', false)
        ->assertSee('<loc>'.e(route('home')).'</loc>', false)
        ->assertSee('<loc>'.e(route('api.docs')).'</loc>', false)
        ->assertSee('<loc>'.e(route('api.playground')).'</loc>', false)
        ->assertSee('<loc>'.e(route('holidays.calendar')).'</loc>', false)
        ->assertSee('<loc>'.e(route('holidays.calendar', ['year' => now()->year])).'</loc>', false)
        ->assertSee('<loc>'.e(route('holidays.calendar', ['year' => now()->year, 'state_code' => 'SBH'])).'</loc>', false);
});

test('robots endpoint references sitemap location', function () {
    $response = $this->get(route('robots'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('User-agent: *', false)
        ->assertSee('Allow: /', false)
        ->assertSee('Disallow: /admin', false)
        ->assertSee('Disallow: /admin/', false)
        ->assertSee('Sitemap: '.route('sitemap'), false);
});
