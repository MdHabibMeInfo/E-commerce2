<?php

use App\Models\Order;
use App\Models\User;
use App\Services\Courier\CourierManager;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

it('allows a customer to view their own order details', function () {
    $customer = User::factory()->create(['account_type' => 'customer', 'status' => 'active']);
    $order = Order::factory()->create([
        'user_id' => $customer->id,
        'customer_name' => $customer->name,
        'phone' => '01710000001',
        'address' => 'House 1, Test Street',
        'city' => 'Dhaka',
        'status' => 'confirmed',
        'payment_status' => 'paid',
    ]);

    $this->actingAs($customer)
        ->get(route('account.orders.show', $order))
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee('Order Details');
});

it('allows a customer to cancel their own pending order', function () {
    $customer = User::factory()->create(['account_type' => 'customer', 'status' => 'active']);
    $order = Order::factory()->create([
        'user_id' => $customer->id,
        'status' => 'pending',
    ]);

    $this->actingAs($customer)
        ->get(route('account.orders.show', $order))
        ->assertOk()
        ->assertSee('Cancel order');

    $this->actingAs($customer)
        ->post(route('account.orders.cancel', $order))
        ->assertRedirect(route('account.orders.show', $order))
        ->assertSessionHas('status', 'Your order has been cancelled.');

    expect($order->fresh()->status)->toBe('cancelled');
});

it('does not allow a customer to cancel an order after it is accepted', function () {
    $customer = User::factory()->create(['account_type' => 'customer', 'status' => 'active']);
    $order = Order::factory()->create([
        'user_id' => $customer->id,
        'status' => 'confirmed',
    ]);

    $this->actingAs($customer)
        ->post(route('account.orders.cancel', $order))
        ->assertForbidden();

    expect($order->fresh()->status)->toBe('confirmed');
});

it('does not allow a customer to cancel a pending order with a courier shipment', function () {
    $customer = User::factory()->create(['account_type' => 'customer', 'status' => 'active']);
    $order = Order::factory()->create([
        'user_id' => $customer->id,
        'status' => 'pending',
    ]);
    $order->shipments()->create([
        'courier_code' => 'steadfast',
        'consignment_id' => 'SF-2001',
        'tracking_code' => 'TRK-2001',
        'status' => 'created',
    ]);

    $this->actingAs($customer)
        ->get(route('account.orders.show', $order))
        ->assertOk()
        ->assertDontSee('Cancel order');

    $this->actingAs($customer)
        ->post(route('account.orders.cancel', $order))
        ->assertForbidden();

    expect($order->fresh()->status)->toBe('pending');
});

it('does not allow a customer to cancel another customers pending order', function () {
    $customer = User::factory()->create(['account_type' => 'customer', 'status' => 'active']);
    $otherCustomer = User::factory()->create(['account_type' => 'customer', 'status' => 'active']);
    $order = Order::factory()->create([
        'user_id' => $otherCustomer->id,
        'status' => 'pending',
    ]);

    $this->actingAs($customer)
        ->post(route('account.orders.cancel', $order))
        ->assertForbidden();

    expect($order->fresh()->status)->toBe('pending');
});

it('shows only the customers past orders in order history', function () {
    $customer = User::factory()->create(['account_type' => 'customer', 'status' => 'active']);
    $pastOrder = Order::factory()->create([
        'user_id' => $customer->id,
        'status' => 'delivered',
    ]);
    $activeOrder = Order::factory()->create([
        'user_id' => $customer->id,
        'status' => 'processing',
    ]);
    $otherCustomersPastOrder = Order::factory()->create([
        'status' => 'delivered',
    ]);

    $this->actingAs($customer)
        ->get(route('account.orders.history'))
        ->assertOk()
        ->assertSee('Order History')
        ->assertSee($pastOrder->order_number)
        ->assertDontSee($activeOrder->order_number)
        ->assertDontSee($otherCustomersPastOrder->order_number)
        ->assertDontSee('Profile details');
});

it('prevents a customer from viewing another customers order', function () {
    $customer = User::factory()->create(['account_type' => 'customer', 'status' => 'active']);
    $otherCustomer = User::factory()->create(['account_type' => 'customer', 'status' => 'active']);
    $order = Order::factory()->create([
        'user_id' => $otherCustomer->id,
        'customer_name' => $otherCustomer->name,
        'phone' => '01710000002',
        'address' => 'House 2, Test Street',
        'city' => 'Chattogram',
        'status' => 'confirmed',
    ]);

    $this->actingAs($customer)
        ->get(route('account.orders.show', $order))
        ->assertForbidden();
});

it('prevents duplicate courier shipment creation for the same order', function () {
    $admin = createSystemOwnerUser();
    $this->actingAs(completeManagementTwoFactorForTest($admin));

    $order = Order::factory()->create([
        'customer_name' => 'Courier Customer',
        'phone' => '01710000003',
        'address' => 'House 3, Test Street',
        'city' => 'Sylhet',
        'status' => 'confirmed',
    ]);

    $order->shipments()->create([
        'courier_code' => 'steadfast',
        'consignment_id' => 'SF-1001',
        'tracking_code' => 'TRK-1001',
        'status' => 'created',
    ]);

    $manager = app(CourierManager::class);

    $result = $manager->createShipment($order, 'steadfast');

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('already exists');
});

it('refuses shipment creation when the courier is disabled or unconfigured', function () {
    config()->set('couriers.couriers.steadfast.enabled', false);
    config()->set('couriers.couriers.steadfast.api_url', null);
    config()->set('couriers.couriers.steadfast.api_key', null);

    $order = Order::factory()->create([
        'customer_name' => 'Courier Customer',
        'phone' => '01710000004',
        'address' => 'House 4, Test Street',
        'city' => 'Khulna',
        'status' => 'confirmed',
    ]);

    $result = app(CourierManager::class)->createShipment($order, 'steadfast');

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('not active or configured')
        ->and($order->shipments()->count())->toBe(0);
});

it('does not advertise courier stubs as available when credentials are present', function () {
    config()->set('couriers.couriers.steadfast.enabled', true);
    config()->set('couriers.couriers.steadfast.api_url', 'https://portal.steadfast.com.bd');
    config()->set('couriers.couriers.steadfast.api_key', 'configured-account-key');
    config()->set('couriers.couriers.pathao.enabled', true);
    config()->set('couriers.couriers.pathao.api_url', 'https://merchant.pathao.com');
    config()->set('couriers.couriers.pathao.api_key', 'configured-account-key');

    $availableCodes = array_map(
        fn ($courier) => $courier->code(),
        app(CourierManager::class)->availableCouriers(),
    );

    expect($availableCodes)
        ->not->toContain('steadfast')
        ->not->toContain('pathao');
});

it('creates a REDX parcel with the official request contract and persists its tracking ID', function () {
    Http::preventStrayRequests();
    Http::fake([
        'openapi.redx.com.bd/v1.0.0-beta/areas*' => Http::response([
            'areas' => [
                ['id' => 17, 'name' => 'Dhanmondi'],
            ],
        ]),
        'openapi.redx.com.bd/v1.0.0-beta/parcel' => Http::response([
            'tracking_id' => 'REDX-TRACK-1001',
        ]),
    ]);
    Cache::forget('couriers.redx.areas.'.hash('sha256', 'dhaka'));
    config()->set('couriers.couriers.redx.enabled', true);
    config()->set('couriers.couriers.redx.environment', 'production');
    config()->set('couriers.couriers.redx.api_token', 'redx-test-token');
    config()->set('couriers.couriers.redx.pickup_store_id', '3');
    config()->set('couriers.couriers.redx.default_weight_grams', 500);

    $order = Order::factory()->create([
        'customer_name' => 'REDX Customer',
        'phone' => '01710000005',
        'address' => 'House 5, Test Street',
        'city' => 'Dhaka',
        'area' => 'Dhanmondi',
        'payment_method' => 'cod',
        'subtotal' => 1000,
        'delivery_fee' => 60,
        'total' => 1060,
        'status' => 'confirmed',
    ]);

    $result = app(CourierManager::class)->createShipment($order, 'redx');

    expect($result['success'])->toBeTrue()
        ->and($result['tracking_code'])->toBe('REDX-TRACK-1001');
    expect($order->fresh()->status)->toBe('processing');
    $this->assertDatabaseHas('courier_shipments', [
        'order_id' => $order->id,
        'courier_code' => 'redx',
        'tracking_code' => 'REDX-TRACK-1001',
        'status' => 'created',
    ]);

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://openapi.redx.com.bd/v1.0.0-beta/parcel'
        && $request->hasHeader('API-ACCESS-TOKEN', 'Bearer redx-test-token')
        && $request['delivery_area_id'] === 17
        && $request['cash_collection_amount'] === '1060'
        && $request['merchant_invoice_id'] === $order->order_number
    );
});

it('keeps a REDX shipment outcome unknown and blocks a blind duplicate retry', function () {
    Http::preventStrayRequests();
    $parcelRequests = 0;
    Http::fake([
        'openapi.redx.com.bd/v1.0.0-beta/areas*' => Http::response([
            'areas' => [
                ['id' => 17, 'name' => 'Dhanmondi'],
            ],
        ]),
        'openapi.redx.com.bd/v1.0.0-beta/parcel' => function (Request $request) use (&$parcelRequests) {
            $parcelRequests++;

            return Http::response([], 200);
        },
    ]);
    Cache::forget('couriers.redx.areas.'.hash('sha256', 'dhaka'));
    config()->set('couriers.couriers.redx.enabled', true);
    config()->set('couriers.couriers.redx.environment', 'production');
    config()->set('couriers.couriers.redx.api_token', 'redx-test-token');
    config()->set('couriers.couriers.redx.pickup_store_id', '3');
    config()->set('couriers.couriers.redx.default_weight_grams', 500);

    $order = Order::factory()->create([
        'customer_name' => 'REDX Customer',
        'phone' => '01710000006',
        'address' => 'House 6, Test Street',
        'city' => 'Dhaka',
        'area' => 'Dhanmondi',
        'payment_method' => 'cod',
        'status' => 'confirmed',
    ]);
    $manager = app(CourierManager::class);

    $firstAttempt = $manager->createShipment($order, 'redx');
    $secondAttempt = $manager->createShipment($order, 'redx');

    expect($firstAttempt['success'])->toBeFalse()
        ->and($secondAttempt['success'])->toBeFalse()
        ->and($order->shipments()->firstOrFail()->status)->toBe('unknown')
        ->and($parcelRequests)->toBe(1);
});

it('does not cache a temporary REDX area lookup failure', function () {
    Http::preventStrayRequests();
    Http::fake([
        'openapi.redx.com.bd/v1.0.0-beta/areas*' => Http::sequence()
            ->push(['message' => 'Temporarily unavailable'], 503)
            ->push(['areas' => [['id' => 17, 'name' => 'Dhanmondi']]], 200),
        'openapi.redx.com.bd/v1.0.0-beta/parcel' => Http::response([
            'tracking_id' => 'REDX-TRACK-1003',
        ]),
    ]);
    Cache::forget('couriers.redx.areas.'.hash('sha256', 'dhaka'));
    config()->set('couriers.couriers.redx.enabled', true);
    config()->set('couriers.couriers.redx.environment', 'production');
    config()->set('couriers.couriers.redx.api_token', 'redx-test-token');
    config()->set('couriers.couriers.redx.pickup_store_id', '3');
    config()->set('couriers.couriers.redx.default_weight_grams', 500);
    $order = Order::factory()->create([
        'customer_name' => 'REDX Customer',
        'phone' => '01710000009',
        'address' => 'House 9, Test Street',
        'city' => 'Dhaka',
        'area' => 'Dhanmondi',
        'payment_method' => 'cod',
        'status' => 'confirmed',
    ]);
    $manager = app(CourierManager::class);

    $firstAttempt = $manager->createShipment($order, 'redx');
    $secondAttempt = $manager->createShipment($order, 'redx');

    expect($firstAttempt['success'])->toBeFalse()
        ->and($secondAttempt['success'])->toBeTrue()
        ->and($order->shipments()->firstOrFail()->tracking_code)->toBe('REDX-TRACK-1003');

    Http::assertSentCount(3);
});

it('shows the latest REDX status on the customer order details page', function () {
    Http::preventStrayRequests();
    Http::fake([
        'openapi.redx.com.bd/v1.0.0-beta/parcel/info/REDX-TRACK-1002' => Http::response([
            'parcel' => [
                'tracking_id' => 'REDX-TRACK-1002',
                'status' => 'delivery-in-progress',
            ],
        ]),
    ]);
    Cache::forget('couriers.redx.tracking.'.hash('sha256', 'REDX-TRACK-1002'));
    config()->set('couriers.couriers.redx.enabled', true);
    config()->set('couriers.couriers.redx.environment', 'production');
    config()->set('couriers.couriers.redx.api_token', 'redx-test-token');
    config()->set('couriers.couriers.redx.pickup_store_id', '3');
    config()->set('couriers.couriers.redx.default_weight_grams', 500);
    $customer = User::factory()->create(['account_type' => 'customer', 'status' => 'active']);
    $order = Order::factory()->create([
        'user_id' => $customer->id,
        'status' => 'processing',
    ]);
    $order->shipments()->create([
        'courier_code' => 'redx',
        'tracking_code' => 'REDX-TRACK-1002',
        'status' => 'created',
    ]);

    $this->actingAs($customer)
        ->get(route('account.orders.show', $order))
        ->assertOk()
        ->assertSee('delivery-in-progress');
});

it('authenticates and applies REDX webhook status events idempotently', function () {
    config()->set('couriers.couriers.redx.webhook_token', 'redx-webhook-test-secret');
    $order = Order::factory()->create(['status' => 'processing']);
    $shipment = $order->shipments()->create([
        'courier_code' => 'redx',
        'tracking_code' => 'REDX-WEBHOOK-1001',
        'status' => 'created',
    ]);
    $event = [
        'tracking_number' => 'REDX-WEBHOOK-1001',
        'timestamp' => now()->toISOString(),
        'status' => 'delivered',
        'message_en' => 'Parcel delivered',
        'invoice_number' => $order->order_number,
        'delivery_type' => 'regular',
    ];

    $this->postJson(route('webhooks.redx', ['token' => 'invalid-token']), $event)
        ->assertNotFound();
    expect($shipment->fresh()->status)->toBe('created');

    $validUrl = route('webhooks.redx', ['token' => 'redx-webhook-test-secret']);
    $this->postJson($validUrl, $event)
        ->assertOk()
        ->assertJson(['received' => true]);

    expect($shipment->fresh()->status)->toBe('delivered')
        ->and($order->fresh()->status)->toBe('delivered')
        ->and($shipment->fresh()->delivered_at)->not->toBeNull();

    $this->postJson($validUrl, $event)
        ->assertOk()
        ->assertJson(['received' => true, 'duplicate' => true]);

    expect($shipment->fresh()->status)->toBe('delivered');
});
