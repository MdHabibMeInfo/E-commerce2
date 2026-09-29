<?php

use App\Models\SecurityEvent;
use App\Models\User;
use App\Notifications\LoginNotification;
use App\Notifications\PasswordResetNotification;
use App\Services\TotpService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('renders the admin sign-in form', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Sign in')
        ->assertSee('name="email"', false)
        ->assertSee('name="password"', false);
});

it('renders the customer registration form', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Create account');
});

it('shows password recovery and uses an enumeration-resistant response', function () {
    Notification::fake();
    $user = createSystemOwnerUser(['email' => 'password-reset-owner@example.com']);

    $this->get(route('password.request'))->assertOk();
    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => 'missing-password-owner@example.com'])
        ->assertSessionHas('status', 'If an account matches that email address, a password reset link will be sent.');

    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHas('status', 'If an account matches that email address, a password reset link will be sent.');

    Notification::assertSentTo($user, PasswordResetNotification::class);
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
    $totp = app(TotpService::class);
    Notification::fake();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.two-factor.setup'));

    $this->assertAuthenticatedAs($user);
    Notification::assertNothingSent();
    $this->get(route('admin'))->assertRedirect(route('admin.two-factor.setup'));

    $this->get(route('admin.two-factor.setup'))
        ->assertOk()
        ->assertSee('Secret key')
        ->assertSee('Authenticator setup QR code', false)
        ->assertSee('data:image/svg+xml;base64,', false);
    $secret = Crypt::decryptString(session('security.pending_two_factor_secret'));

    $this->post(route('admin.two-factor.confirm'), [
        'code' => $totp->codeAt($secret, now()->timestamp),
    ])->assertRedirect(route('account.security'));

    $this->get(route('admin'))->assertOk();
    $this->assertDatabaseHas('security_events', [
        'user_id' => $user->id,
        'action' => 'two_factor_enabled',
    ]);
});

it('requires an unverified system owner to verify email before authenticator enrollment', function () {
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
        ->get('/email/verify')
        ->assertOk();

    $this->post('/email/verification-notification')
        ->assertRedirect()
        ->assertSessionHas('status', 'A new verification link has been sent to your email address.');

    Notification::assertSentTo($user, VerifyEmail::class);
    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->getKey(), 'hash' => sha1($user->email)],
    );

    $this->get($verificationUrl)->assertRedirect();
    $this->assertNotNull($user->fresh()->email_verified_at);
    $this->assertDatabaseHas('security_events', [
        'user_id' => $user->id,
        'action' => 'email_verified',
    ]);
    $this->get(route('admin'))->assertRedirect(route('admin.two-factor.setup'));

    Notification::assertSentTo($user, VerifyEmail::class);
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

it('allows customer sign-in without sending routine security mail', function () {
    $customer = User::factory()->create(['email' => 'customer@example.com']);
    Notification::fake();

    $this->post(route('login.store'), [
        'email' => $customer->email,
        'password' => 'password',
    ])->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($customer);
    $this->assertDatabaseHas('security_events', [
        'user_id' => $customer->id,
        'action' => 'login_success',
    ]);
    Notification::assertNothingSent();
});

it('emails management users for unfamiliar completed logins without repeating known-device alerts', function () {
    Notification::fake();
    $owner = createSystemOwnerUser(['email' => 'new-device-owner@example.com']);
    $secret = app(TotpService::class)->generateSecret();
    $owner->forceFill([
        'two_factor_secret' => $secret,
        'two_factor_recovery_codes' => [],
        'two_factor_confirmed_at' => now(),
    ])->save();

    $this->post(route('login.store'), [
        'email' => $owner->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.challenge'));
    $this->post(route('two-factor.verify'), [
        'code' => app(TotpService::class)->codeAt($secret, now()->timestamp),
    ])->assertRedirect(route('admin'));

    Notification::assertSentToTimes($owner, LoginNotification::class, 1);
    $loginEvent = SecurityEvent::query()
        ->where('user_id', $owner->id)
        ->where('action', 'login_success')
        ->latest('id')
        ->firstOrFail();
    expect($loginEvent->context['session_fingerprint'])->toHaveLength(64);

    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->post(route('login.store'), [
        'email' => $owner->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.challenge'));
    $this->post(route('two-factor.verify'), [
        'code' => app(TotpService::class)->codeAt($secret, now()->addSeconds(30)->timestamp),
    ])->assertRedirect(route('admin'));

    Notification::assertSentToTimes($owner, LoginNotification::class, 1);
    $this->assertDatabaseCount('security_events', 5);
    expect(SecurityEvent::query()->where('action', 'login_success')->count())->toBe(2);
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
        ->assertDontSee('Admin notifications');

    $this->get(route('admin.orders'))->assertForbidden();
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

    $this->put(route('admin.roles.update', $role), ['name' => 'Catalog Lead'])
        ->assertRedirect(route('admin.roles.index'));
    $this->patch(route('admin.roles.status.update', $role), ['is_active' => false])
        ->assertRedirect(route('admin.roles.index'));
    $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'Catalog Lead', 'is_active' => false]);
    $this->delete(route('admin.roles.destroy', $role))->assertRedirect(route('admin.roles.index'));
    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});

it('prevents staff permissions from granting role or user administration', function () {
    $permissionNames = ['roles.create', 'roles.update', 'users.create', 'users.update', 'users.suspend'];
    $permissions = collect($permissionNames)->map(fn (string $name) => Permission::findOrCreate($name, 'web'));
    $role = Role::create(['name' => 'Privileged Staff', 'guard_name' => 'web']);
    $role->syncPermissions($permissions);
    $staff = User::factory()->create();
    $staff->forceFill(['account_type' => 'admin'])->save();
    $staff->assignRole($role);
    $target = User::factory()->create();
    $target->forceFill(['account_type' => 'admin'])->save();
    $target->assignRole(Role::create(['name' => 'Target Staff', 'guard_name' => 'web']));
    $this->actingAs(completeManagementTwoFactorForTest($staff));

    $this->post(route('admin.roles.store'), ['name' => 'Escalated Role'])
        ->assertForbidden();
    $this->post(route('admin.users.store'), [
        'name' => 'Escalated User',
        'email' => 'escalated@example.com',
        'password' => 'StrongPassword123!',
        'password_confirmation' => 'StrongPassword123!',
        'roles' => [$role->id],
    ])->assertForbidden();
    $this->patch(route('admin.users.status.update', $target), ['status' => 'suspended'])
        ->assertForbidden();

    $this->assertDatabaseMissing('users', ['email' => 'escalated@example.com']);
    expect($target->fresh()->status)->toBe('active');
});

it('keeps the fixed system owner role immutable and unavailable for staff assignment', function () {
    $owner = createSystemOwnerUser();
    $systemRole = Role::query()->where('name', 'System Owner')->where('guard_name', 'web')->firstOrFail();
    $this->actingAs(completeManagementTwoFactorForTest($owner));

    $this->put(route('admin.roles.update', $systemRole), ['name' => 'Changed Owner'])
        ->assertNotFound();
    $this->delete(route('admin.roles.destroy', $systemRole))->assertNotFound();
    $this->post(route('admin.users.store'), [
        'name' => 'Impersonator',
        'email' => 'impersonator@example.com',
        'password' => 'StrongPassword123!',
        'password_confirmation' => 'StrongPassword123!',
        'roles' => [$systemRole->id],
    ])->assertSessionHasErrors('roles.0');

    $this->assertDatabaseHas('roles', ['id' => $systemRole->id, 'name' => 'System Owner', 'is_system' => true]);
    $this->assertDatabaseMissing('users', ['email' => 'impersonator@example.com']);
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
        'password_change_required' => true,
    ]);
    $this->assertDatabaseHas('model_has_roles', [
        'role_id' => $role->id,
        'model_id' => $user->id,
        'model_type' => User::class,
    ]);
    $this->delete(route('admin.roles.destroy', $role))->assertUnprocessable();
    $this->assertDatabaseHas('security_events', [
        'user_id' => $owner->id,
        'action' => 'staff_account_created',
    ]);
    expect($user->password_change_required)->toBeTrue();
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
    $user = completeManagementTwoFactorForTest(createSystemOwnerUser());

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
    $this->assertDatabaseHas('security_events', [
        'user_id' => $user->id,
        'action' => 'logout',
    ]);
    $this->get(route('admin'))->assertRedirect(route('login'));
});

it('creates the first system owner through the protected console prompt', function () {
    Notification::fake();

    $this->artisan('admin:create')
        ->expectsQuestion('Admin name', 'First Admin')
        ->expectsQuestion('Admin email', 'first-admin@example.com')
        ->expectsQuestion('Password (12+ characters, mixed case, number, symbol)', 'StrongPassword123!')
        ->expectsQuestion('Confirm password', 'StrongPassword123!')
        ->expectsOutputToContain('A verification link was sent to the provided email address.')
        ->assertExitCode(0);

    $this->assertDatabaseHas('users', [
        'email' => 'first-admin@example.com',
        'account_type' => 'admin',
        'is_system_owner' => true,
        'email_verified_at' => null,
    ]);

    $user = User::query()->where('email', 'first-admin@example.com')->firstOrFail();
    Notification::assertSentTo($user, VerifyEmail::class);
    expect($user->getDirectPermissions())->toHaveCount(Permission::query()->count());
});

it('does not create a second system owner', function () {
    createSystemOwnerUser();

    $this->artisan('admin:create')
        ->expectsOutputToContain('A system owner already exists.')
        ->assertExitCode(1);

    $this->assertDatabaseCount('users', 1);
});

it('seeds permissions without creating users and preserves the fixed owner role', function () {
    $this->seed();

    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseHas('roles', [
        'name' => 'System Owner',
        'guard_name' => 'web',
        'is_system' => true,
    ]);
});
