<?php

test('redirects guests from the root to login', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('login'));
});
