<?php

beforeEach(function () {
    config(['app.app_type' => 'default']);
});

it('sends self-hosted visitors of marketing pages to the login', function (string $uri) {
    $this->get($uri)->assertRedirect(route('login'));
})->with(['/', '/de', '/imprint', '/de/imprint', '/blog', '/blog/server-side-analytics-laravel']);

it('sends self-hosted docs links to the public docs', function () {
    $this->get('/docs/installation.html')->assertRedirect('https://simplestats.io/docs/installation.html');
    $this->get('/docs')->assertRedirect('https://simplestats.io/docs/');
});

it('keeps the marketing pages on the saas', function () {
    config(['app.app_type' => 'saas']);

    $this->get('/imprint')->assertOk();
});
