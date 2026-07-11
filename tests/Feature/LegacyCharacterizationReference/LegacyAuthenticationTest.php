<?php

namespace Tests\Feature\LegacyCharacterizationReference;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('legacy login page renders with legacy public entry point', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/Login')
            ->where('legacyApplicationTitle', 'Centralized Automated Meter Reading')
            ->where('canResetPassword', true),
        );
});

test('legacy login page shows configured test credential hint when enabled', function () {
    config()->set('camr.login_hint.enabled', true);
    config()->set('camr.login_hint.label', 'Test login');
    config()->set('camr.login_hint.username', 'admin');
    config()->set('camr.login_hint.password', '123456');

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/Login')
            ->where('loginCredentialHint', [
                'label' => 'Test login',
                'username' => 'admin',
                'password' => '123456',
            ]),
        );
});

test('legacy login page hides test credential hint when disabled', function () {
    config()->set('camr.login_hint.enabled', false);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/Login')
            ->where('loginCredentialHint', null),
        );
});

test('legacy login succeeds and preserves loginID session contract', function () {
    $user = User::factory()->create([
        'name' => 'admin',
        'password' => Hash::make('123456'),
    ]);

    $this->post('/login-user', [
        'user_name' => 'admin',
        'InputPassword' => '123456',
    ])
        ->assertRedirect('/site');

    $this->assertAuthenticatedAs($user);
    $this->assertSame($user->id, session('loginID'));
});

test('legacy login fails when provided only email, preserving username-based auth', function () {
    $user = User::factory()->create([
        'name' => 'admin',
        'email' => 'admin@example.test',
        'password' => Hash::make('123456'),
    ]);

    $this->from('/')
        ->post('/login-user', [
            'user_name' => 'admin@example.test',
            'InputPassword' => '123456',
        ])
        ->assertRedirect('/')
        ->assertSessionHas('fail', 'This Username is not Registered.');

    $this->assertGuest();
    $this->assertNull(session('loginID'));
    $this->assertDatabaseMissing('users', ['id' => $user->id, 'name' => 'admin@example.test']);
});

test('legacy login keeps fail message when username does not exist', function () {
    $this->from('/')
        ->post('/login-user', [
            'user_name' => 'missing',
            'InputPassword' => '123456',
        ])
        ->assertRedirect('/')
        ->assertSessionHas('fail', 'This Username is not Registered.');
});

test('legacy login keeps fail message when password is wrong', function () {
    User::factory()->create([
        'name' => 'admin',
        'password' => Hash::make('123456'),
    ]);

    $this->from('/')
        ->post('/login-user', [
            'user_name' => 'admin',
            'InputPassword' => 'wrong-password',
        ])
        ->assertRedirect('/')
        ->assertSessionHas('fail', 'Incorrect Password');
});

test('legacy protected route redirects anonymous users with legacy fail message', function () {
    $this->get('/site')
        ->assertRedirect('/')
        ->assertSessionHas('fail', 'You Have to Login First');
});

test('legacy password reset page renders with legacy elements', function () {
    $this->get('/passwordreset')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/ForgotPassword')
            ->where('legacyApplicationTitle', 'Centralized Automated Meter Reading'),
        );
});

test('legacy password reset validates missing email', function () {
    $response = $this->postJson('/reset-password', [
        'user_email_address' => '',
    ]);

    $this->assertSame(422, $response->getStatusCode());

    $payload = json_decode($response->getContent(), true);

    $this->assertIsArray($payload);
    $this->assertSame('Email Address is Required', $payload['errors']['user_email_address'][0] ?? null);
});

test('legacy password reset reports unknown email and known email success responses', function () {
    $user = User::factory()->create([
        'name' => 'admin',
        'email' => 'admin@example.test',
        'password' => Hash::make('123456'),
    ]);

    $this->postJson('/reset-password', [
        'user_email_address' => 'admin@example.test',
    ])
        ->assertOk()
        ->assertJson(['success' => 'Email sent successfully!']);

    $this->postJson('/reset-password', [
        'user_email_address' => 'missing@example.test',
    ])
        ->assertOk()
        ->assertJson(['success' => 'Email Not Found!']);

    expect(
        Hash::check('123456', $user->refresh()->password)
    )->toBeFalse();
});
