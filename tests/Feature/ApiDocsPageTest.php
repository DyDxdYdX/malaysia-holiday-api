<?php

test('api docs page returns successful response with key sections', function () {
    $response = $this->get(route('api.docs'));

    $response
        ->assertOk()
        ->assertSee('API Documentation (v1)')
        ->assertSee('GET /api/v1/states')
        ->assertSee('GET /api/v1/holidays')
        ->assertSee('GET /api/v1/holidays/check')
        ->assertSee('X-API-Key');
});
