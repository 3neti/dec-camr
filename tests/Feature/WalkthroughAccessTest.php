<?php

test('walkthrough gate is disabled by default', function () {
    $this->get('/')->assertOk();
});

test('walkthrough gate covers login and public device routes', function (string $method, string $uri) {
    config()->set('camr.walkthrough_access.enabled', true);
    config()->set('camr.walkthrough_access.username', 'preview');
    config()->set('camr.walkthrough_access.password', 'secret');

    $this->call($method, $uri)
        ->assertUnauthorized()
        ->assertHeader('WWW-Authenticate', 'Basic realm="CAMR Walkthrough"');
})->with([
    ['GET', '/'],
    ['POST', '/login-user'],
    ['POST', '/http_post_server.php'],
    ['GET', '/rtu/index.php/rtu/rtu_check_update/aa:bb/reset_update_csv'],
]);

test('walkthrough gate fails closed without credentials', function () {
    config()->set('camr.walkthrough_access.enabled', true);

    $this->get('/')->assertUnauthorized();
});

test('walkthrough credentials allow the application login', function () {
    config()->set('camr.walkthrough_access.enabled', true);
    config()->set('camr.walkthrough_access.username', 'preview');
    config()->set('camr.walkthrough_access.password', 'secret');

    $this->withBasicAuth('preview', 'secret')->get('/')->assertOk();
});
