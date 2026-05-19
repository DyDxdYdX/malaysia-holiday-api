<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('api validation errors use standard error envelope', function () {
    $this->getJson('/api/v1/holidays')
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.message', 'The given data was invalid.');
});

test('api not found errors use standard error envelope', function () {
    $this->getJson('/api/v1/unknown-endpoint')
        ->assertStatus(404)
        ->assertJsonPath('error.code', 'NOT_FOUND');
});
