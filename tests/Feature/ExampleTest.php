<?php

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertSee('A free, high-integrity public holiday API for Malaysia')
        ->assertDontSee(route('login'));
});

test('public pages do not show a login button but login route is accessible', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee(route('login'));

    $this->get(route('holidays.calendar'))
        ->assertOk()
        ->assertDontSee(route('login'));

    $this->get(route('login'))
        ->assertOk();
});
