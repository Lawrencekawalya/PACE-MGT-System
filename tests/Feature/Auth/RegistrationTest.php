<?php

test('public staff registration is disabled', function () {
    $this->get('/register')->assertNotFound();

    $this->post('/register', [
        'name' => 'Unapproved User',
        'email' => 'unapproved@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    $this->assertGuest();
});
