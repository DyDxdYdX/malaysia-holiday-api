<?php

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertSee('Malaysia public holiday data, reviewed before it ships.');
});
