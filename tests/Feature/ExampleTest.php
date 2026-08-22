<?php

use function Pest\Laravel\get;

it('redirects guests to the login page', function () {
    get('/srodki')->assertRedirect(route('login'));
});

it('redirects the root url to the assets list', function () {
    get('/')->assertRedirect('/srodki');
});

it('serves the login page', function () {
    get('/login')->assertOk()->assertSee('Zaloguj się');
});
