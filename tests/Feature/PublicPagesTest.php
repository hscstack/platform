<?php

test('the homepage loads successfully', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

test('the about us page loads successfully', function () {
    $response = $this->get('/about-us');

    $response->assertStatus(200);
});

test('non-existent public resources render the 404 error page', function () {
    $response = $this->get('/resources/999999');

    $response->assertStatus(200);
    $response->assertSee('errors\/404');
});
