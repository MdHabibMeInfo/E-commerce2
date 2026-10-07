<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Notifications\SecurityAlertNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('lets the system owner suspend and reactivate customers with notifications and session revocation', function () {
    config(['session.driver' => 'database']);
    $owner = completeManagementTwoFactorForTest(createSystemOwnerUser());
    $customer = User::factory()->create([
        'account_type' => 'customer',
        'email' => 'customer-to-suspend@example.com',
    ]);
    $token = $customer->createToken('customer-device');
    DB::table('sessions')->insert([
        'id' => 'customer-session-to-revoke',
        'user_id' => $customer->id,
        'ip_address' => '192.0.2.90',
        'user_agent' => 'test-agent',
        'payload' => '',
        'last_activity' => time(),
    ]);
    Notification::fake();

    $this->actingAs($owner)
        ->get(route('admin.customers.index', ['search' => 'customer-to-suspend']))
        ->assertOk()
        ->assertSee($customer->email)
        ->assertSee('Customer Accounts');

    $this->patch(route('admin.customers.status.update', $customer), ['status' => 'suspended'])
        ->assertRedirect(route('admin.customers.index'));

    expect($customer->fresh()->status)->toBe('suspended')
        ->and($customer->tokens()->count())->toBe(0);
    $this->assertDatabaseMissing('sessions', ['id' => 'customer-session-to-revoke']);
    Notification::assertSentTo($customer, SecurityAlertNotification::class);
    $this->assertDatabaseHas('security_events', [
        'user_id' => $owner->id,
        'action' => 'customer_status_updated',
    ]);

    $this->patch(route('admin.customers.status.update', $customer->fresh()), ['status' => 'active'])
        ->assertRedirect(route('admin.customers.index'));
    expect($customer->fresh()->status)->toBe('active');
});

it('requires the customer management permission to view customer accounts', function () {
    $role = Role::create(['name' => 'Customer Observer', 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::findOrCreate('products.view', 'web'));
    $staff = User::factory()->create(['account_type' => 'admin']);
    $staff->assignRole($role);

    $this->actingAs(completeManagementTwoFactorForTest($staff))
        ->get(route('admin.customers.index'))
        ->assertForbidden();

    $customer = User::factory()->create(['account_type' => 'customer']);

    $this->get(route('admin.customers.show', $customer))
        ->assertForbidden();
});

it('shows customer account details with their orders and reviews', function () {
    $owner = completeManagementTwoFactorForTest(createSystemOwnerUser());
    $customer = User::factory()->create([
        'account_type' => 'customer',
        'name' => 'Nadia Customer',
        'email' => 'nadia.customer@example.com',
        'phone' => '01700000001',
        'profile_photo_path' => 'profile-photos/nadia.jpg',
    ]);
    Order::factory()->create([
        'user_id' => $customer->id,
        'order_number' => 'ORD-CUSTOMER-DETAIL',
        'total' => 1250,
    ]);
    $product = Product::create([
        'name' => 'Customer Review Product',
        'slug' => 'customer-review-product',
        'price' => 50,
        'stock' => 10,
        'type' => 'simple',
        'is_active' => true,
    ]);
    ProductReview::factory()->create([
        'product_id' => $product->id,
        'name' => $customer->name,
        'email' => $customer->email,
        'body' => 'A detailed customer review.',
    ]);

    $this->actingAs($owner)
        ->get(route('admin.customers.show', $customer))
        ->assertOk()
        ->assertSee('Nadia Customer')
        ->assertSee('/storage/profile-photos/nadia.jpg')
        ->assertSee('01700000001')
        ->assertSee('ORD-CUSTOMER-DETAIL')
        ->assertSee('Customer Review Product')
        ->assertSee('A detailed customer review.')
        ->assertSee('Total order value');

    $this->get(route('admin.customers.show', $owner))
        ->assertNotFound();
});

it('shows an initial avatar when a customer has no profile photo', function () {
    $owner = completeManagementTwoFactorForTest(createSystemOwnerUser());
    $customer = User::factory()->create([
        'account_type' => 'customer',
        'name' => 'No Photo Customer',
        'profile_photo_path' => null,
    ]);

    $this->actingAs($owner)
        ->get(route('admin.customers.index'))
        ->assertOk()
        ->assertSee('href="'.route('admin.customers.show', $customer).'"', false)
        ->assertSee('>N</span>', false);
});

it('shows live paid sales, customer, and stock metrics on the dashboard', function () {
    $owner = completeManagementTwoFactorForTest(createSystemOwnerUser());
    User::factory()->create(['account_type' => 'customer', 'status' => 'active']);
    Product::create([
        'name' => 'Low Stock Product',
        'slug' => 'low-stock-dashboard-product',
        'price' => 50,
        'stock' => 3,
        'type' => 'simple',
        'is_active' => true,
    ]);
    Order::factory()->create(['payment_status' => 'paid', 'total' => 125]);
    Order::factory()->create(['payment_status' => 'pending', 'total' => 900]);

    $this->actingAs($owner)
        ->get(route('admin'))
        ->assertOk()
        ->assertSee('Active customers')
        ->assertSee('Payments confirmed')
        ->assertSee('Recent orders')
        ->assertSee('Low Stock Product')
        ->assertSee('Customers')
        ->assertSee('Coupons');
});
