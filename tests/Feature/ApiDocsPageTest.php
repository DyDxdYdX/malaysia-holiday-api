<?php

test('api docs page returns successful response with key sections', function () {
    $response = $this->get(route('api.docs'));

    $response
        ->assertOk()
        ->assertSee('API Documentation (v1)')
        ->assertSee('/api/v1/states')
        ->assertSee('/api/v1/holidays')
        ->assertSee('/api/v1/holidays/check')
        ->assertSee('Supported regions')
        ->assertSee('VALIDATION_ERROR')
        ->assertSee('include_source')
        ->assertSee('Pesta Kaamatan')
        ->assertSee('No API key')
        ->assertDontSee('X-API-Key');
});
