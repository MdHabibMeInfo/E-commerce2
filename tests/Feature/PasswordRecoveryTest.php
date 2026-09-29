<?php

use App\Models\User;
use App\Notifications\PasswordResetNotification;
use App\Notifications\SecurityAlertNotification;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('returns the same forgot-password response for known and unknown addresses', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'recover@example.com']);

    $knownResponse = $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => $user->email]);
    $unknownResponse = $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => 'missing@example.com']);

    $knownResponse->assertRedirect(route('password.request'))
        ->assertSessionHas('status', 'If an account matches that email address, a password reset link will be sent.');
    $unknownResponse->assertRedirect(route('password.request'))
        ->assertSessionHas('status', 'If an account matches that email address, a password reset link will be sent.');

    Notification::assertSentTo($user, PasswordResetNotification::class);
    Notification::assertNotSentTo($user, SecurityAlertNotification::class);
    $this->assertDatabaseHas('security_events', [
        'user_id' => $user->id,
        'action' => 'password_reset_requested',
    ]);
    $this->assertDatabaseMissing('security_events', [
        'action' => 'password_reset_requested',
        'user_id' => null,
    ]);
});

it('queues encrypted reset tokens behind the shared mail provider limit', function () {
    $user = User::factory()->create(['email' => 'queued-reset@example.com']);
    $notification = new PasswordResetNotification('one-time-reset-token');
    $middleware = $notification->middleware($user, 'mail');

    expect($notification)->toBeInstanceOf(ShouldQueue::class)
        ->and($notification)->toBeInstanceOf(ShouldBeEncrypted::class)
        ->and($notification->tries)->toBe(10)
        ->and($middleware)->toHaveCount(1)
        ->and($middleware[0])->toBeInstanceOf(RateLimited::class);
});

it('resets a password once and revokes previous sessions and api tokens', function () {
    Notification::fake();
    config(['session.driver' => 'database']);
    $user = User::factory()->create(['email' => 'reset-once@example.com']);
    $token = Password::createToken($user);
    $user->createToken('old-api-token');
    DB::table('sessions')->insert([
        'id' => 'reset-session-to-revoke',
        'user_id' => $user->id,
        'ip_address' => '192.0.2.40',
        'user_agent' => 'test-agent',
        'payload' => '',
        'last_activity' => time(),
    ]);

    $payload = [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewSecurePassword456!',
        'password_confirmation' => 'NewSecurePassword456!',
    ];

    $this->post(route('password.update'), $payload)
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Your password has been reset. Sign in with your new password.');

    expect(Hash::check($payload['password'], $user->fresh()->password))->toBeTrue();
    $this->assertDatabaseMissing('sessions', ['id' => 'reset-session-to-revoke']);
    $this->assertDatabaseCount('personal_access_tokens', 0);
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    $this->assertDatabaseHas('security_events', [
        'user_id' => $user->id,
        'action' => 'password_reset_completed',
    ]);

    $this->post(route('password.update'), $payload)->assertSessionHasErrors('email');
});

it('sends management security mail after a password reset', function () {
    Notification::fake();
    $owner = createSystemOwnerUser(['email' => 'owner-reset@example.com']);
    $token = Password::createToken($owner);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $owner->email,
        'password' => 'NewSecurePassword456!',
        'password_confirmation' => 'NewSecurePassword456!',
    ])->assertRedirect(route('login'));

    Notification::assertSentTo($owner, SecurityAlertNotification::class);
});

it('rejects an expired password reset token without changing the password', function () {
    $user = User::factory()->create(['email' => 'expired-reset@example.com']);
    $token = Password::createToken($user);
    $oldPassword = $user->password;
    $this->travel(61)->minutes();

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewSecurePassword456!',
        'password_confirmation' => 'NewSecurePassword456!',
    ])->assertSessionHasErrors('email');

    expect($user->fresh()->password)->toBe($oldPassword);
    $this->assertDatabaseMissing('security_events', [
        'user_id' => $user->id,
        'action' => 'password_reset_completed',
    ]);
});

it('rate limits forgot-password requests by email and ip', function () {
    Notification::fake();

    foreach (range(1, 5) as $attempt) {
        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'throttle@example.com'])
            ->assertRedirect(route('password.request'));
    }

    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => 'throttle@example.com'])
        ->assertTooManyRequests();
});
