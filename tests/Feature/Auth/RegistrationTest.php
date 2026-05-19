<?php

test('registration screen is disabled', function () {
    $this->get('/register')
        ->assertNotFound();
});

test('new users cannot register publicly', function () {
    $this->post('/register', [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    $this->assertGuest();
});
