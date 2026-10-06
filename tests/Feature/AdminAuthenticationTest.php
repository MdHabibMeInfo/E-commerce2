<?php

use App\Models\Order;
use App\Models\User;
use App\Notifications\LoginNotification;
use App\Notifications\PasswordResetNotification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('renders the admin sign-in form', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Sign in')
        ->assertSee('name="email"', false)
        ->assertSee('name="password"', false)
        ->assertSee('aria-controls="login-password"', false);
});

it('renders the customer registration form', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Create account')
        ->assertSee('aria-controls="register-password"', false)
        ->assertSee('aria-controls="register-password-confirmation"', false);
});

it('renders the password reset request form', function () {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('Reset password');
});

it('registers a customer account without email verification or notifications', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'name' => 'New Customer',
        'email' => 'new-customer@example.com',
        'phone' => '01712345678',
        'password' => 'StrongPassword123!',
        'password_confirmation' => 'StrongPassword123!',
    ])->assertRedirect(route('home'));

    $customer = User::query()->where('email', 'new-customer@example.com')->firstOrFail();
    $this->assertAuthenticatedAs($customer);
    $this->assertDatabaseHas('users', [
        'id' => $customer->id,
        'account_type' => 'customer',
        'status' => 'active',
        'phone' => '01712345678',
    ]);
    Notification::assertNothingSent();
});

it('rejects weak passwords during customer registration', function () {
    $this->post(route('register.store'), [
        'name' => 'New Customer',
        'email' => 'weak-password@example.com',
        'password' => 'passwordpassword',
        'password_confirmation' => 'passwordpassword',
    ])->assertSessionHasErrors('password');

    $this->assertDatabaseMissing('users', ['email' => 'weak-password@example.com']);
});

it('redirects guests to sign in before showing admin pages', function () {
    $this->get(route('admin'))
        ->assertRedirect(route('login'));
});

it('requires system owners to enroll an authenticator before dashboard access', function () {
    $user = createSystemOwnerUser(['email' => 'owner@example.com']);
    Notification::fake();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.two-factor.setup'));

    $this->assertAuthenticatedAs($user);
    Notification::assertNothingSent();
    $this->get(route('admin'))->assertRedirect(route('admin.two-factor.setup'));
});

it('requires unverified system owners to verify email before authenticator enrollment', function () {
    $user = createSystemOwnerUser(['email' => 'unverified-owner@example.com']);
    $user->forceFill(['email_verified_at' => null])->save();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('verification.notice'));

    $this->assertAuthenticatedAs($user);
    $this->get(route('admin'))->assertRedirect(route('verification.notice'));
});

it('sends a signed verification email and verifies the management account', function () {
    $user = createSystemOwnerUser(['email' => 'verify-owner@example.com']);
    $user->forceFill(['email_verified_at' => null])->save();
    Notification::fake();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk();

    $this->post(route('verification.send'))
        ->assertRedirect()
        ->assertSessionHas('status', 'A new verification link has been sent to your email address.');

    Notification::assertSentTo($user, VerifyEmail::class);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->getKey(), 'hash' => sha1($user->email)],
    );

    $this->get($verificationUrl)->assertRedirect(route('admin'));
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('sends a password reset email without revealing whether an account exists', function () {
    $user = User::factory()->create(['email' => 'reset-user@example.com']);
    Notification::fake();

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertRedirect()
        ->assertSessionHas('status', 'If an account exists for that email, a reset link has been sent.');

    Notification::assertSentTo($user, PasswordResetNotification::class);
});

it('returns the same password reset response for an unknown email', function () {
    Notification::fake();

    $this->post(route('password.email'), ['email' => 'unknown@example.com'])
        ->assertRedirect()
        ->assertSessionHas('status', 'If an account exists for that email, a reset link has been sent.');

    Notification::assertNothingSent();
});

it('resets the password once and invalidates database sessions', function () {
    $user = User::factory()->create(['email' => 'reset-user@example.com']);
    $token = Password::broker()->createToken($user);
    DB::table('sessions')->insert([
        'id' => 'other-device-session',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test-agent',
        'payload' => '',
        'last_activity' => time(),
    ]);

    $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
        ->assertOk()
        ->assertSee('Choose a new password')
        ->assertSee('aria-controls="reset-password"', false)
        ->assertSee('aria-controls="reset-password-confirmation"', false);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewSecurePassword456!',
        'password_confirmation' => 'NewSecurePassword456!',
    ])->assertRedirect(route('login'));

    expect(Hash::check('NewSecurePassword456!', $user->fresh()->password))->toBeTrue();
    $this->assertDatabaseMissing('sessions', ['id' => 'other-device-session']);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'AnotherSecurePassword789!',
        'password_confirmation' => 'AnotherSecurePassword789!',
    ])->assertSessionHasErrors('email');
});

it('rejects an invalid password reset token without changing the password', function () {
    $user = User::factory()->create(['email' => 'reset-user@example.com']);
    $existingPasswordHash = $user->password;

    $this->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'NewSecurePassword456!',
        'password_confirmation' => 'NewSecurePassword456!',
    ])->assertSessionHasErrors('email');

    expect($user->fresh()->password)->toBe($existingPasswordHash);
});

it('rejects weak passwords during password reset', function () {
    $user = User::factory()->create(['email' => 'reset-user@example.com']);
    $token = Password::broker()->createToken($user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'passwordpassword',
        'password_confirmation' => 'passwordpassword',
    ])->assertSessionHasErrors('password');
});

it('rejects invalid credentials without authenticating the user', function () {
    $user = createSystemOwnerUser(['email' => 'owner@example.com']);
    Notification::fake();

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    Notification::assertNotSentTo($user, LoginNotification::class);
});

it('denies dashboard access to authenticated non-admin users', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin'))
        ->assertForbidden();
});

it('rejects suspended customer accounts without sending a sign-in notification', function () {
    $user = User::factory()->create([
        'email' => 'suspended-customer@example.com',
        'status' => 'suspended',
    ]);
    Notification::fake();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
    Notification::assertNotSentTo($user, LoginNotification::class);
});

it('allows a customer account to sign in and receive a login notification', function () {
    $customer = User::factory()->create(['email' => 'customer@example.com']);
    Notification::fake();

    $this->post(route('login.store'), [
        'email' => $customer->email,
        'password' => 'password',
    ])->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($customer);
    Notification::assertNothingSent();
});

it('limits admin roles to their assigned resources', function () {
    $permission = Permission::findOrCreate('products.view', 'web');
    $role = Role::findOrCreate('Product Manager', 'web');
    $role->givePermissionTo($permission);

    $user = User::factory()->create();
    $user->forceFill([
        'account_type' => 'admin',
        'status' => 'active',
    ])->save();
    $user->assignRole($role);

    $this->actingAs(completeManagementTwoFactorForTest($user))
        ->get(route('admin.products.index'))
        ->assertOk()
        ->assertDontSee('Admin notifications')
        ->assertDontSee(route('admin.orders'), false);

    $this->get(route('admin.orders'))->assertForbidden();
    $this->get(route('admin.profile'))
        ->assertOk()
        ->assertSee(route('account.orders'), false)
        ->assertDontSee(route('admin.orders'), false);

    $ownOrder = Order::factory()->create(['user_id' => $user->id]);
    $otherOrder = Order::factory()->create();

    $this->get(route('account.orders.show', $ownOrder))
        ->assertOk()
        ->assertSee($ownOrder->order_number);

    $this->get(route('account.orders.show', $otherOrder))
        ->assertForbidden();
});

it('allows active staff with a custom role to access the admin dashboard', function () {
    $permission = Permission::findOrCreate('orders.view', 'web');
    $role = Role::create([
        'name' => 'Fulfillment Lead',
        'guard_name' => 'web',
    ]);
    $role->givePermissionTo($permission);

    $user = User::factory()->create();
    $user->forceFill(['account_type' => 'admin'])->save();
    $user->assignRole($role);

    expect($user->status)->toBe('active')
        ->and($user->account_type)->toBe('admin')
        ->and($user->roles()->where('is_active', true)->exists())->toBeTrue()
        ->and($user->can('access-admin'))->toBeTrue();

    $this->actingAs(completeManagementTwoFactorForTest($user))
        ->get(route('admin'))
        ->assertOk();
});

it('keeps customer accounts out of admin routes even when they have a staff permission', function () {
    $permission = Permission::findOrCreate('orders.view', 'web');
    $role = Role::create([
        'name' => 'Customer Order Viewer',
        'guard_name' => 'web',
    ]);
    $role->givePermissionTo($permission);
    $customer = User::factory()->create();
    $customer->assignRole($role);

    $this->actingAs($customer)
        ->get(route('admin.orders'))
        ->assertForbidden();
});

it('lets the system owner create a role with selected permissions', function () {
    $owner = createSystemOwnerUser();
    $permission = Permission::findOrCreate('products.view', 'web');

    $this->actingAs(completeManagementTwoFactorForTest($owner))
        ->post(route('admin.roles.store'), [
            'name' => 'Catalog Reviewer',
            'permissions' => [$permission->id],
        ])
        ->assertRedirect(route('admin.roles.index'));

    $this->get(route('admin.roles.index'))
        ->assertOk()
        ->assertSee('Roles & Permissions');

    $role = Role::query()->where('name', 'Catalog Reviewer')->firstOrFail();
    $this->assertDatabaseHas('role_has_permissions', [
        'role_id' => $role->id,
        'permission_id' => $permission->id,
    ]);
});

it('validates role names without coercing malformed input', function () {
    $this->actingAs(completeManagementTwoFactorForTest(createSystemOwnerUser()))
        ->post(route('admin.roles.store'), [
            'name' => ['unexpected'],
            'permissions' => [],
        ])
        ->assertSessionHasErrors('name');
});

it('lets the system owner create an admin account with an assigned role', function () {
    $owner = createSystemOwnerUser();
    $role = Role::create([
        'name' => 'Order Coordinator',
        'guard_name' => 'web',
    ]);
    Notification::fake();

    $this->actingAs(completeManagementTwoFactorForTest($owner))
        ->post(route('admin.users.store'), [
            'name' => 'Order Staff',
            'email' => 'order-staff@example.com',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'roles' => [$role->id],
        ])
        ->assertRedirect(route('admin.users.index'));

    $user = User::query()->where('email', 'order-staff@example.com')->firstOrFail();
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'account_type' => 'admin',
        'status' => 'active',
    ]);
    $this->assertDatabaseHas('model_has_roles', [
        'role_id' => $role->id,
        'model_id' => $user->id,
        'model_type' => User::class,
    ]);
    Notification::assertSentTo($user, VerifyEmail::class);

    $this->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('order-staff@example.com');

    $this->actingAs(completeManagementTwoFactorForTest($owner))
        ->patch(route('admin.users.status.update', $user), ['status' => 'suspended'])
        ->assertRedirect(route('admin.users.index'));

    $user->refresh()->markEmailAsVerified();
    $this->actingAs($user)
        ->get(route('admin'))
        ->assertForbidden();
});

it('signs out the admin and invalidates dashboard access', function () {
    $this->actingAs(createSystemOwnerUser())
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
    $this->get(route('admin'))->assertRedirect(route('login'));
});

it('creates the first system owner through the protected console prompt', function () {
    Notification::fake();

    $this->artisan('admin:create')
        ->expectsQuestion('Admin name', 'First Admin')
        ->expectsQuestion('Admin email', 'first-admin@example.com')
        ->expectsQuestion('Password (12+ characters, mixed case, number, symbol)', 'StrongPassword123!')
        ->expectsQuestion('Confirm password', 'StrongPassword123!')
        ->expectsOutputToContain('System owner account created.')
        ->assertExitCode(0);

    $this->assertDatabaseHas('users', [
        'email' => 'first-admin@example.com',
        'account_type' => 'admin',
        'is_system_owner' => true,
        'email_verified_at' => null,
    ]);

    $user = User::query()->where('email', 'first-admin@example.com')->firstOrFail();
    Notification::assertNothingSent();
    expect($user->getDirectPermissions())->toHaveCount(Permission::query()->count());
});

it('does not create a second system owner', function () {
    createSystemOwnerUser();

    $this->artisan('admin:create')
        ->expectsOutputToContain('A system owner already exists.')
        ->assertExitCode(1);

    $this->assertDatabaseCount('users', 1);
});

it('seeds permissions without creating users or predefined roles', function () {
    $this->seed();

    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseHas('roles', [
        'name' => 'System Owner',
        'guard_name' => 'web',
        'is_system' => true,
    ]);
});
