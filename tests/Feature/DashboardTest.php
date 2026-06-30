<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('legacy dashboard requires legacy login session', function () {
    $response = $this->get('/site');

    $response->assertRedirect('/');
    $response->assertSessionHas('fail', 'You Have to Login First');
});

test('legacy dashboard renders when loginID session exists', function () {
    $user = User::factory()->create();

    $response = $this->withSession(['loginID' => $user->id])->get('/site');

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Dashboard'));
});

test('legacy dashboard and modern dashboard are separate entry points', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});
