<?php

use App\Models\Order;
use App\Models\User;
use App\Notifications\EmailChangeVerificationNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

it('shows profile and notification actions without storefront header controls', function () {
    $user = createSystemOwnerUser();
    expect($user->status)->toBe('active');
    expect($user->is_system_owner)->toBeTrue();
    expect($user->can('access-admin'))->toBeTrue();
    $this->actingAs(completeManagementTwoFactorForTest($user));

    $response = $this->get(route('admin'));

    $response->assertOk()
        ->assertSee(route('admin.profile'), false)
        ->assertSee(route('admin.notifications'), false)
        ->assertSee('aria-label="Admin notifications"', false)
        ->assertDontSee('Search in...')
        ->assertDontSee('Wishlist')
        ->assertDontSee('Shopping cart');
});

it('redirects guests away from the admin profile', function () {
    $this->get(route('admin.profile'))
        ->assertRedirect(route('login'));
});

it('displays the signed-in admin profile details', function () {
    $user = createSystemOwnerUser([
        'name' => 'Admin Profile',
        'email' => 'admin-profile@example.com',
    ]);

    $this->actingAs(completeManagementTwoFactorForTest($user))
        ->get(route('admin.profile'))
        ->assertOk()
        ->assertSee('Profile')
        ->assertSee('Admin Profile')
        ->assertSee('admin-profile@example.com')
        ->assertSee('Save changes');
});

it('shows recent orders as admin notifications', function () {
    $this->actingAs(completeManagementTwoFactorForTest(createSystemOwnerUser()));

    Order::create([
        'order_number' => 'ORD-ADMIN-1001',
        'customer_name' => 'Test Customer',
        'phone' => '01700000000',
        'address' => 'Test address',
        'city' => 'Dhaka',
        'subtotal' => 500,
        'total' => 500,
        'status' => 'pending',
    ]);

    $this->get(route('admin.notifications'))
        ->assertOk()
        ->assertSee('New order received')
        ->assertSee('ORD-ADMIN-1001')
        ->assertSee('Test Customer');
});

it('counts new order notifications until the notification page is opened', function () {
    $admin = completeManagementTwoFactorForTest(createSystemOwnerUser());
    $admin->forceFill([
        'admin_notifications_last_seen_order_id' => (int) (Order::query()->max('id') ?? 0),
    ])->save();
    $this->actingAs($admin);

    Order::create([
        'order_number' => 'ORD-ADMIN-1002',
        'customer_name' => 'Unread Customer',
        'phone' => '01700000001',
        'address' => 'Test address',
        'city' => 'Dhaka',
        'subtotal' => 800,
        'total' => 800,
        'status' => 'pending',
    ]);

    $this->getJson(route('admin.notifications.unread'))
        ->assertOk()
        ->assertJsonPath('count', 1);

    $this->get(route('admin.notifications'))->assertOk();

    $this->getJson(route('admin.notifications.unread'))
        ->assertOk()
        ->assertJsonPath('count', 0);

    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->actingAs(completeManagementTwoFactorForTest($admin));

    $this->getJson(route('admin.notifications.unread'))
        ->assertOk()
        ->assertJsonPath('count', 0);

    Order::create([
        'order_number' => 'ORD-ADMIN-1003',
        'customer_name' => 'New Customer After Read',
        'phone' => '01700000002',
        'address' => 'Test address',
        'city' => 'Dhaka',
        'subtotal' => 900,
        'total' => 900,
        'status' => 'pending',
    ]);

    $this->getJson(route('admin.notifications.unread'))
        ->assertOk()
        ->assertJsonPath('count', 1);
});

it('requires a signed-in user to update profile details', function () {
    $this->put(route('admin.profile.update'), [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'phone' => '01700000002',
    ])->assertRedirect(route('login'));
});

it('requires email verification before an admin email change is complete', function () {
    $user = createSystemOwnerUser([
        'name' => 'Original Name',
        'email' => 'original@example.com',
        'phone' => '01700000003',
    ]);
    Notification::fake();

    $this->actingAs(completeManagementTwoFactorForTest($user))
        ->put(route('admin.profile.update'), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '01700000004',
            'current_password' => 'password',
        ])
        ->assertSessionHasErrors('email');

    expect($user->fresh()->email)->toBe('original@example.com');

    $this->put(route('account.security.email.update'), [
        'email' => 'updated@example.com',
        'current_password' => 'password',
    ])->assertRedirect();

    expect($user->fresh()->email)->toBe('original@example.com')
        ->and($user->fresh()->pending_email)->toBe('updated@example.com');
    Notification::assertSentOnDemand(EmailChangeVerificationNotification::class);
});

it('rejects profile details that belong to another user', function () {
    $user = createSystemOwnerUser(['email' => 'owner@example.com']);
    User::factory()->create(['email' => 'other@example.com']);

    $this->actingAs(completeManagementTwoFactorForTest($user))
        ->from(route('admin.profile'))
        ->put(route('admin.profile.update'), [
            'name' => 'Updated Name',
            'email' => 'other@example.com',
            'phone' => null,
        ])
        ->assertSessionHasErrors('email');

    expect($user->fresh()->email)->toBe('owner@example.com');
});

it('requires the current password before changing the profile email', function () {
    $user = createSystemOwnerUser(['email' => 'current-password-owner@example.com']);

    $this->actingAs(completeManagementTwoFactorForTest($user))
        ->put(route('account.security.email.update'), [
            'email' => 'new-email@example.com',
            'current_password' => 'incorrect-password',
        ])
        ->assertSessionHasErrors('current_password');

    expect($user->fresh()->email)->toBe('current-password-owner@example.com');
});

it('keeps email verified when updating non-email profile details', function () {
    $user = createSystemOwnerUser(['email' => 'admin@example.com']);
    $verifiedAt = $user->email_verified_at;

    $this->actingAs(completeManagementTwoFactorForTest($user))
        ->put(route('admin.profile.update'), [
            'name' => 'New Admin Name',
            'email' => $user->email,
            'phone' => '01700000005',
        ])
        ->assertRedirect(route('admin.profile'));

    expect($user->fresh()->email_verified_at->equalTo($verifiedAt))->toBeTrue();
});

it('stores a replacement profile photo and removes the previous file', function () {
    Storage::fake('public');
    $oldPhotoPath = UploadedFile::fake()->image('old-profile.jpg')->store('profile-photos', 'public');
    $user = createSystemOwnerUser([
        'email' => 'photo-admin@example.com',
        'profile_photo_path' => $oldPhotoPath,
    ]);

    $this->actingAs(completeManagementTwoFactorForTest($user))
        ->put(route('admin.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'profile_photo' => UploadedFile::fake()->image('new-profile.png'),
        ])
        ->assertRedirect(route('admin.profile'));

    $newPhotoPath = $user->fresh()->profile_photo_path;
    expect($newPhotoPath)->not->toBe($oldPhotoPath);
    Storage::disk('public')->assertMissing($oldPhotoPath);
    Storage::disk('public')->assertExists($newPhotoPath);
});

it('removes the signed-in user profile photo', function () {
    Storage::fake('public');
    $photoPath = 'profile-photos/admin-photo.jpg';
    Storage::disk('public')->put($photoPath, 'fake-image-content');
    $user = createSystemOwnerUser([
        'email' => 'photo-remove@example.com',
        'profile_photo_path' => $photoPath,
    ]);

    $this->actingAs(completeManagementTwoFactorForTest($user))
        ->delete(route('admin.profile.photo.destroy'))
        ->assertRedirect(route('admin.profile'));

    expect($user->fresh()->profile_photo_path)->toBeNull();
    Storage::disk('public')->assertMissing($photoPath);
});

it('rejects non-image profile photos without removing the current photo', function () {
    Storage::fake('public');
    $photoPath = 'profile-photos/admin-photo.jpg';
    Storage::disk('public')->put($photoPath, 'fake-image-content');
    $user = createSystemOwnerUser([
        'email' => 'photo-invalid@example.com',
        'profile_photo_path' => $photoPath,
    ]);

    $this->actingAs(completeManagementTwoFactorForTest($user))
        ->from(route('admin.profile'))
        ->put(route('admin.profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'profile_photo' => UploadedFile::fake()->create('not-an-image.txt', 10, 'text/plain'),
        ])
        ->assertSessionHasErrors('profile_photo');

    expect($user->fresh()->profile_photo_path)->toBe($photoPath);
    Storage::disk('public')->assertExists($photoPath);
});
