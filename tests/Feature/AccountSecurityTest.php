<?php

use App\Models\Order;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Notifications\EmailChangeVerificationNotification;
use App\Services\TotpService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('renders the customer security page and its account controls', function () {
    $customer = User::factory()->create([
        'account_type' => 'customer',
        'status' => 'active',
    ]);

    $this->actingAs($customer)
        ->get(route('account.security'))
        ->assertOk()
        ->assertSee('Security')
        ->assertSee('Change password')
        ->assertSee('Active sessions')
        ->assertSee('Recent security activity');
});

it('keeps past orders out of the customer profile page', function () {
    $customer = User::factory()->create([
        'account_type' => 'customer',
        'status' => 'active',
        'profile_photo_path' => 'profile-photos/customer-avatar.jpg',
    ]);
    $activeOrder = Order::factory()->create([
        'user_id' => $customer->id,
        'status' => 'processing',
    ]);
    $pastOrder = Order::factory()->create([
        'user_id' => $customer->id,
        'status' => 'delivered',
    ]);

    $this->actingAs($customer)
        ->get(route('account.profile'))
        ->assertOk()
        ->assertSee('Profile details')
        ->assertSee(route('logout'), false)
        ->assertSee('Sign out')
        ->assertSee('data-profile-photo-input', false)
        ->assertSee('data-profile-photo-preview', false)
        ->assertSee('form="account-profile-photo-remove"', false)
        ->assertSee($activeOrder->order_number)
        ->assertDontSee($pastOrder->order_number);
});

it('keeps a customer email unchanged until the new address confirms its signed link', function () {
    $user = User::factory()->create(['email' => 'old-address@example.com']);
    $user->createToken('before-email-change');
    Notification::fake();

    $this->actingAs($user)
        ->put(route('account.security.email.update'), [
            'email' => ' New-Address@example.com ',
            'current_password' => 'password',
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'Confirm the link sent to your new email address to complete the change.');

    $user->refresh();
    expect($user->email)->toBe('old-address@example.com')
        ->and($user->pending_email)->toBe('new-address@example.com')
        ->and($user->email_verified_at)->not->toBeNull();
    $this->assertDatabaseHas('security_events', [
        'user_id' => $user->id,
        'action' => 'email_change_requested',
    ]);
    Notification::assertSentOnDemand(EmailChangeVerificationNotification::class);

    $this->post(route('account.security.email.resend'), ['current_password' => 'password'])
        ->assertRedirect()
        ->assertSessionHas('status', 'A new confirmation link was sent to the pending email address.');
    $this->assertDatabaseHas('security_events', [
        'user_id' => $user->id,
        'action' => 'email_change_verification_resent',
    ]);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.email-change',
        now()->addHours(12),
        ['user' => $user->getKey(), 'hash' => sha1($user->pending_email)],
    );
    $expiredVerificationUrl = URL::temporarySignedRoute(
        'verification.email-change',
        now()->subMinute(),
        ['user' => $user->getKey(), 'hash' => sha1($user->pending_email)],
    );

    $this->get($expiredVerificationUrl)->assertForbidden();
    expect($user->fresh()->email)->toBe('old-address@example.com');

    $this->get($verificationUrl)->assertRedirect(route('account.security'));

    $this->assertDatabaseCount('personal_access_tokens', 0);
    expect($user->fresh()->email)->toBe('new-address@example.com')
        ->and($user->fresh()->pending_email)->toBeNull()
        ->and($user->fresh()->email_verified_at)->not->toBeNull();
    $this->assertDatabaseHas('security_events', [
        'user_id' => $user->id,
        'action' => 'email_changed',
    ]);
});

it('rejects an email change when the current password is incorrect', function () {
    $user = User::factory()->create(['email' => 'old-address@example.com']);

    $this->actingAs($user)
        ->put(route('account.security.email.update'), [
            'email' => 'new-address@example.com',
            'current_password' => 'wrong-password',
        ])
        ->assertSessionHasErrors('current_password');

    expect($user->fresh()->email)->toBe('old-address@example.com');
    $this->assertDatabaseMissing('security_events', ['action' => 'email_changed']);
});

it('lets customers update profile details without sending activity email', function () {
    $customer = User::factory()->create(['name' => 'Old Name', 'phone' => '01700000007']);
    Notification::fake();

    $this->actingAs($customer)
        ->put(route('account.profile.update'), [
            'name' => 'New Name',
            'phone' => '01700000008',
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'Profile details updated.');

    expect($customer->fresh()->name)->toBe('New Name')
        ->and($customer->fresh()->phone)->toBe('01700000008');
    $this->assertDatabaseHas('security_events', [
        'user_id' => $customer->id,
        'action' => 'profile_updated',
    ]);
    Notification::assertNothingSent();
});

it('changes a password and revokes all other database sessions', function () {
    $user = User::factory()->create();
    $user->createToken('before-password-change');
    config(['session.driver' => 'database']);
    $this->actingAs($user);
    DB::table('sessions')->insert([
        'id' => 'other-device-session',
        'user_id' => $user->id,
        'ip_address' => '192.0.2.10',
        'user_agent' => 'test-agent',
        'payload' => '',
        'last_activity' => time(),
    ]);

    $this->put(route('account.security.password.update'), [
        'current_password' => 'password',
        'password' => 'NewSecurePassword456!',
        'password_confirmation' => 'NewSecurePassword456!',
    ])->assertRedirect()->assertSessionHas('status', 'Password updated. Other sessions were signed out.');

    expect(Hash::check('NewSecurePassword456!', $user->fresh()->password))->toBeTrue();
    expect($user->fresh()->password_change_required)->toBeFalse();
    $this->assertDatabaseCount('personal_access_tokens', 0);
    $this->assertDatabaseMissing('sessions', ['id' => 'other-device-session']);
    $this->assertDatabaseHas('security_events', [
        'user_id' => $user->id,
        'action' => 'password_changed',
    ]);
});

it('blocks management tools until a required temporary password is changed', function () {
    $user = createSystemOwnerUser();
    $user->forceFill(['password_change_required' => true])->save();
    $this->actingAs(completeManagementTwoFactorForTest($user));

    $this->get(route('admin'))->assertRedirect(route('account.security'));
    $this->put(route('account.security.password.update'), [
        'current_password' => 'password',
        'password' => 'NewSecurePassword456!',
        'password_confirmation' => 'NewSecurePassword456!',
    ])->assertRedirect();

    expect($user->fresh()->password_change_required)->toBeFalse();
    $this->get(route('admin'))->assertOk();
});

it('requires a fresh authenticator code before entering management routes', function () {
    $user = completeManagementTwoFactorForTest(createSystemOwnerUser());
    $totp = app(TotpService::class);
    session()->forget('security.two_factor_verified_at');

    $this->actingAs($user)
        ->withSession(['security.pending_login_after_mfa' => true])
        ->get(route('admin'))
        ->assertRedirect(route('two-factor.challenge'));
    $this->post(route('two-factor.verify'), [
        'code' => $totp->codeAt($user->two_factor_secret, now()->timestamp),
    ])->assertRedirect(route('admin'));

    $this->get(route('admin'))->assertOk();
    $this->assertDatabaseHas('security_events', [
        'user_id' => $user->id,
        'action' => 'login_success',
    ]);
});

it('requires an authenticator challenge for customer sign-ins after opt-in', function () {
    $customer = User::factory()->create(['email' => 'mfa-customer@example.com']);
    $secret = app(TotpService::class)->generateSecret();
    $customer->forceFill([
        'two_factor_secret' => $secret,
        'two_factor_recovery_codes' => [],
        'two_factor_confirmed_at' => now(),
    ])->save();

    $this->post(route('login.store'), [
        'email' => $customer->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.challenge'));

    $this->post(route('two-factor.verify'), [
        'code' => app(TotpService::class)->codeAt($secret, now()->timestamp),
    ])->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($customer);
    $this->assertDatabaseHas('security_events', [
        'user_id' => $customer->id,
        'action' => 'login_success',
    ]);
});

it('lets a customer enroll an authenticator and receives one-time recovery codes', function () {
    $customer = User::factory()->create();
    $totp = app(TotpService::class);

    $this->actingAs($customer)
        ->get(route('account.security'))
        ->assertOk()
        ->assertSee('Set up authenticator');

    $this->get(route('account.security.two-factor.setup'))
        ->assertOk()
        ->assertSee('Secret key');
    $secret = Crypt::decryptString(session('security.pending_two_factor_secret'));

    $this->post(route('account.security.two-factor.confirm'), [
        'code' => $totp->codeAt($secret, now()->timestamp),
    ])->assertRedirect(route('account.security'));

    $this->get(route('account.security'))
        ->assertOk()
        ->assertSee('Save these recovery codes');

    expect($customer->fresh()->two_factor_confirmed_at)->not->toBeNull()
        ->and($customer->fresh()->two_factor_recovery_codes)->toHaveCount(8);
});

it('resets the system owner authenticator only after explicit console confirmation', function () {
    $owner = createSystemOwnerUser();
    $owner->forceFill([
        'two_factor_secret' => app(TotpService::class)->generateSecret(),
        'two_factor_recovery_codes' => [Hash::make('A1B2C3D4E5F60718')],
        'two_factor_confirmed_at' => now(),
        'two_factor_last_used_at' => now(),
    ])->save();
    DB::table('sessions')->insert([
        'id' => 'owner-session-to-revoke',
        'user_id' => $owner->id,
        'ip_address' => '192.0.2.14',
        'user_agent' => 'test-agent',
        'payload' => '',
        'last_activity' => time(),
    ]);

    $this->artisan('admin:two-factor-reset', ['email' => $owner->email])
        ->expectsConfirmation('Reset the system owner authenticator and revoke all sessions?', 'yes')
        ->expectsOutputToContain('Authenticator reset.')
        ->assertExitCode(0);

    expect($owner->fresh()->two_factor_secret)->toBeNull()
        ->and($owner->fresh()->two_factor_confirmed_at)->toBeNull();
    $this->assertDatabaseMissing('sessions', ['id' => 'owner-session-to-revoke']);
    $this->assertDatabaseHas('security_events', [
        'user_id' => $owner->id,
        'action' => 'two_factor_reset_from_console',
    ]);
});

it('consumes a recovery code exactly once during management sign-in', function () {
    $user = createSystemOwnerUser();
    $recoveryCode = 'A1B2-C3D4-E5F6-0718';
    $user->forceFill([
        'two_factor_secret' => app(TotpService::class)->generateSecret(),
        'two_factor_recovery_codes' => [Hash::make(str_replace('-', '', $recoveryCode))],
        'two_factor_confirmed_at' => now(),
    ])->save();

    $this->actingAs($user)->get(route('admin'))->assertRedirect(route('two-factor.challenge'));
    $this->post(route('two-factor.verify'), ['code' => $recoveryCode])->assertRedirect(route('admin'));

    expect($user->fresh()->two_factor_recovery_codes)->toBe([]);
    $this->assertDatabaseHas('security_events', [
        'user_id' => $user->id,
        'action' => 'two_factor_recovery_code_used',
    ]);
});

it('rejects reuse of a TOTP code within the same time step', function () {
    $user = createSystemOwnerUser();
    $secret = app(TotpService::class)->generateSecret();
    $user->forceFill([
        'two_factor_secret' => $secret,
        'two_factor_recovery_codes' => [],
        'two_factor_confirmed_at' => now(),
    ])->save();
    $timestamp = now()->timestamp;
    $code = app(TotpService::class)->codeAt($secret, $timestamp);

    expect(app(TotpService::class)->consumeTotpCode($user, $code, $timestamp))->toBeTrue();
    expect(app(TotpService::class)->consumeTotpCode($user, $code, $timestamp))->toBeFalse();
});

it('forbids management bearer tokens from API access without a verified MFA session', function () {
    $owner = createSystemOwnerUser();
    $token = $owner->createToken('security-test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/user')
        ->assertForbidden()
        ->assertJson(['message' => 'Management access requires a verified authenticator session.']);
});

it('requires verified email before management API requests', function () {
    $owner = createSystemOwnerUser(['email' => 'unverified-api-owner@example.com']);
    $owner->forceFill(['email_verified_at' => null])->save();
    $token = $owner->createToken('unverified-management')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/user')
        ->assertForbidden()
        ->assertJson(['message' => 'Management access requires a verified email address.']);
});

it('records failed authenticator attempts without granting management access', function () {
    $user = createSystemOwnerUser();
    $user->forceFill([
        'two_factor_secret' => app(TotpService::class)->generateSecret(),
        'two_factor_recovery_codes' => [],
        'two_factor_confirmed_at' => now(),
    ])->save();

    $this->actingAs($user)->get(route('admin'))->assertRedirect(route('two-factor.challenge'));
    $this->post(route('two-factor.verify'), ['code' => '000000'])
        ->assertSessionHasErrors('code');
    $this->get(route('admin'))->assertRedirect(route('two-factor.challenge'));

    $this->assertDatabaseHas('security_events', [
        'user_id' => $user->id,
        'action' => 'two_factor_failed',
    ]);
});

it('rate limits authenticator challenges per account', function () {
    $user = createSystemOwnerUser();
    $user->forceFill([
        'two_factor_secret' => app(TotpService::class)->generateSecret(),
        'two_factor_recovery_codes' => [],
        'two_factor_confirmed_at' => now(),
    ])->save();

    foreach (range(1, 5) as $attempt) {
        $this->actingAs($user)
            ->post(route('two-factor.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');
    }

    $this->actingAs($user)
        ->post(route('two-factor.verify'), ['code' => '000000'])
        ->assertTooManyRequests();
});

it('lists only the signed-in account security events and database sessions', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    config(['session.driver' => 'database']);
    SecurityEvent::query()->create(['user_id' => $user->id, 'action' => 'password_changed']);
    SecurityEvent::query()->create(['user_id' => $otherUser->id, 'action' => 'private_event']);
    DB::table('sessions')->insert([
        'id' => 'listed-session',
        'user_id' => $user->id,
        'ip_address' => '192.0.2.11',
        'user_agent' => 'test-agent',
        'payload' => '',
        'last_activity' => time(),
    ]);

    $this->actingAs($user)
        ->get(route('account.security'))
        ->assertOk()
        ->assertSee('Password Changed')
        ->assertDontSee('private event')
        ->assertSee('192.0.2.11');
});

it('revokes a session only when it belongs to the signed-in account', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    config(['session.driver' => 'database']);
    DB::table('sessions')->insert([
        [
            'id' => 'my-other-session',
            'user_id' => $user->id,
            'ip_address' => '192.0.2.12',
            'user_agent' => 'test-agent',
            'payload' => '',
            'last_activity' => time(),
        ],
        [
            'id' => 'foreign-session',
            'user_id' => $otherUser->id,
            'ip_address' => '192.0.2.13',
            'user_agent' => 'test-agent',
            'payload' => '',
            'last_activity' => time(),
        ],
    ]);

    $this->actingAs($user)
        ->delete(route('account.security.sessions.destroy', 'my-other-session'))
        ->assertRedirect();

    $this->assertDatabaseMissing('sessions', ['id' => 'my-other-session']);
    $this->assertDatabaseHas('sessions', ['id' => 'foreign-session']);
});

it('shows one-time recovery codes when the user replaces them', function () {
    $user = completeManagementTwoFactorForTest(createSystemOwnerUser());
    $totp = app(TotpService::class);

    $response = $this->actingAs($user)->post(route('account.security.recovery-codes'), [
        'current_password' => 'password',
        'code' => $totp->codeAt($user->two_factor_secret, now()->timestamp),
    ]);

    $response->assertRedirect()->assertSessionHas('recovery_codes');
    expect($user->fresh()->two_factor_recovery_codes)->toHaveCount(8);
    $this->assertDatabaseHas('security_events', [
        'user_id' => $user->id,
        'action' => 'two_factor_recovery_codes_regenerated',
    ]);
});
