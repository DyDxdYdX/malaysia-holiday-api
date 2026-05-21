<?php

test('public pages include canonical and social seo metadata', function () {
    $response = $this->get(route('api.docs'));

    $response->assertOk()
        ->assertSee('<link rel="canonical" href="'.route('api.docs').'">', false)
        ->assertSee('<meta property="og:title" content="API Documentation - '.config('app.name', 'Malaysia Holiday API').'">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
        ->assertSee('<meta name="robots" content="index, follow">', false);
});

test('sitemap endpoint exposes crawlable public urls', function () {
    $response = $this->get(route('sitemap'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee('<?xml version="1.0" encoding="UTF-8"?>', false)
        ->assertSee('<loc>'.route('home').'</loc>', false)
        ->assertSee('<loc>'.route('api.docs').'</loc>', false)
        ->assertSee('<loc>'.route('api.playground').'</loc>', false)
        ->assertSee('<loc>'.route('holidays.calendar').'</loc>', false);
});

test('robots endpoint references sitemap location', function () {
    $response = $this->get(route('robots'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('User-agent: *', false)
        ->assertSee('Allow: /', false)
        ->assertSee('Sitemap: '.route('sitemap'), false);
});
