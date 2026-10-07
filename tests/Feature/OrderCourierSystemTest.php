<?php

use App\Models\Order;
use App\Models\User;
use App\Services\Courier\CourierManager;
use App\Services\Courier\PathaoCourier;
use App\Services\Courier\RedxCourier;
use App\Services\Courier\SteadfastCourier;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

it('keeps Pathao unavailable until all merchant booking settings are configured', function () {
    config()->set('couriers.couriers.pathao.enabled', true);
    config()->set('couriers.couriers.pathao.api_url', 'https://api-hermes.pathao.com');
    config()->set('couriers.couriers.pathao.client_id', 'configured-client-id');
    config()->set('couriers.couriers.pathao.client_secret', null);
    config()->set('couriers.couriers.pathao.username', null);
    config()->set('couriers.couriers.pathao.password', null);
    config()->set('couriers.couriers.pathao.store_id', null);

    $availableCodes = array_map(
        fn ($courier) => $courier->code(),
        app(CourierManager::class)->availableCouriers(),
    );

    expect($availableCodes)
        ->not->toContain('pathao');
});

it('creates a Pathao parcel with its documented token and order contracts', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api-hermes.pathao.com/aladdin/api/v1/issue-token' => Http::response([
            'token_type' => 'Bearer',
            'expires_in' => 432000,
            'access_token' => 'pathao-test-access-token',
            'refresh_token' => 'pathao-test-refresh-token',
        ]),
        'api-hermes.pathao.com/aladdin/api/v1/orders' => Http::response([
            'message' => 'Order Created Successfully',
            'data' => [
                'consignment_id' => 'PATHAO-CONSIGNMENT-1001',
                'merchant_order_id' => 'ORD-PATHAO-1001',
                'order_status' => 'Pending',
                'delivery_fee' => 80,
            ],
        ]),
    ]);
    config()->set('couriers.couriers.pathao.enabled', true);
    config()->set('couriers.couriers.pathao.api_url', 'https://api-hermes.pathao.com');
    config()->set('couriers.couriers.pathao.client_id', 'pathao-test-client-id');
    config()->set('couriers.couriers.pathao.client_secret', 'pathao-test-client-secret');
    config()->set('couriers.couriers.pathao.username', 'pathao-test@example.com');
    config()->set('couriers.couriers.pathao.password', 'pathao-test-password');
    config()->set('couriers.couriers.pathao.store_id', '42');
    config()->set('couriers.couriers.pathao.item_weight', 0.5);

    $order = Order::factory()->create([
        'order_number' => 'ORD-PATHAO-1001',
        'customer_name' => 'Pathao Customer',
        'phone' => '01710000031',
        'address' => 'House 31, Test Street',
        'area' => 'Dhanmondi',
        'city' => 'Dhaka',
        'payment_method' => 'cod',
        'total' => 1060,
        'status' => 'confirmed',
    ]);
    $order->items()->create([
        'name' => 'Test item',
        'price' => 530,
        'quantity' => 2,
    ]);

    $result = app(CourierManager::class)->createShipment($order, 'pathao');

    expect($result['success'])->toBeTrue()
        ->and($result['consignment_id'])->toBe('PATHAO-CONSIGNMENT-1001')
        ->and($result['status'])->toBe('pending')
        ->and($order->fresh()->status)->toBe('processing');
    $shipment = $order->shipments()->firstOrFail();
    expect($shipment->request_payload)->toBe([
        'merchant_order_id' => 'ORD-PATHAO-1001',
        'store_id' => 42,
        'item_quantity' => 2,
        'item_weight' => 0.5,
        'amount_to_collect' => 1060,
    ])->and($shipment->response_payload)->toBe([
        'consignment_id' => 'PATHAO-CONSIGNMENT-1001',
        'merchant_order_id' => 'ORD-PATHAO-1001',
        'order_status' => 'pending',
        'delivery_fee' => 80,
    ]);
    expect(json_encode([$result, $shipment->request_payload, $shipment->response_payload]))
        ->not->toContain('pathao-test-client-secret')
        ->not->toContain('pathao-test-password')
        ->not->toContain('pathao-test-access-token')
        ->not->toContain('pathao-test-refresh-token');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://api-hermes.pathao.com/aladdin/api/v1/issue-token'
        && $request['client_id'] === 'pathao-test-client-id'
        && $request['grant_type'] === 'password'
        && $request['username'] === 'pathao-test@example.com'
        && $request['password'] === 'pathao-test-password');
    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://api-hermes.pathao.com/aladdin/api/v1/orders'
        && $request->hasHeader('Authorization', 'Bearer pathao-test-access-token')
        && $request['store_id'] === 42
        && $request['merchant_order_id'] === 'ORD-PATHAO-1001'
        && $request['recipient_phone'] === '01710000031'
        && $request['recipient_address'] === 'House 31, Test Street, Dhanmondi, Dhaka'
        && $request['item_quantity'] === 2
        && $request['amount_to_collect'] === 1060);
});

it('tracks a Pathao consignment through its documented order-info endpoint', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api-hermes.pathao.com/aladdin/api/v1/issue-token' => Http::response([
            'expires_in' => 432000,
            'access_token' => 'pathao-tracking-access-token',
            'refresh_token' => 'pathao-rotated-refresh-token',
        ]),
        'api-hermes.pathao.com/aladdin/api/v1/orders/PATHAO-CONSIGNMENT-1002/info' => Http::response([
            'message' => 'Order info',
            'data' => [
                'consignment_id' => 'PATHAO-CONSIGNMENT-1002',
                'order_status' => 'Delivered',
                'updated_at' => '2026-10-06 12:00:00',
            ],
        ]),
    ]);
    config()->set('couriers.couriers.pathao.enabled', true);
    config()->set('couriers.couriers.pathao.api_url', 'https://api-hermes.pathao.com');
    config()->set('couriers.couriers.pathao.client_id', 'pathao-tracking-client-id');
    config()->set('couriers.couriers.pathao.client_secret', 'pathao-tracking-client-secret');
    config()->set('couriers.couriers.pathao.username', 'pathao-tracking@example.com');
    config()->set('couriers.couriers.pathao.password', 'pathao-tracking-password');
    config()->set('couriers.couriers.pathao.store_id', '43');
    $tokenCacheKey = 'couriers.pathao.access-token.'.hash('sha256', json_encode([
        'https://api-hermes.pathao.com',
        'pathao-tracking-client-id',
        'pathao-tracking-client-secret',
        'pathao-tracking@example.com',
        'pathao-tracking-password',
    ], JSON_THROW_ON_ERROR));
    Cache::put($tokenCacheKey.'.refresh', Crypt::encryptString('pathao-existing-refresh-token'), now()->addMinutes(5));

    $shipment = Order::factory()->create()->shipments()->create([
        'courier_code' => 'pathao',
        'consignment_id' => 'PATHAO-CONSIGNMENT-1002',
        'status' => 'pending',
    ]);

    $tracking = app(PathaoCourier::class)->trackShipment($shipment);

    expect($tracking['available'])->toBeTrue()
        ->and($tracking['current_status'])->toBe('delivered')
        ->and($tracking['last_updated'])->toBe('2026-10-06 12:00:00');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && $request->url() === 'https://api-hermes.pathao.com/aladdin/api/v1/orders/PATHAO-CONSIGNMENT-1002/info'
        && $request->hasHeader('Authorization', 'Bearer pathao-tracking-access-token'));
    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://api-hermes.pathao.com/aladdin/api/v1/issue-token'
        && $request['grant_type'] === 'refresh_token'
        && $request['refresh_token'] === 'pathao-existing-refresh-token');
    $storedAccessToken = Cache::get($tokenCacheKey);
    $storedRefreshToken = Cache::get($tokenCacheKey.'.refresh');
    expect($storedAccessToken)->not->toContain('pathao-tracking-access-token')
        ->and(Crypt::decryptString($storedAccessToken))->toBe('pathao-tracking-access-token')
        ->and($storedRefreshToken)->not->toContain('pathao-rotated-refresh-token')
        ->and(Crypt::decryptString($storedRefreshToken))->toBe('pathao-rotated-refresh-token');
});

it('does not mark Pathao tracking available when the order info omits its status', function () {
    Http::preventStrayRequests();
    Http::fake([
        'api-hermes.pathao.com/aladdin/api/v1/issue-token' => Http::response([
            'expires_in' => 432000,
            'access_token' => 'pathao-invalid-status-access-token',
        ]),
        'api-hermes.pathao.com/aladdin/api/v1/orders/PATHAO-CONSIGNMENT-1003/info' => Http::response([
            'data' => ['consignment_id' => 'PATHAO-CONSIGNMENT-1003'],
        ]),
    ]);
    config()->set('couriers.couriers.pathao.enabled', true);
    config()->set('couriers.couriers.pathao.api_url', 'https://api-hermes.pathao.com');
    config()->set('couriers.couriers.pathao.client_id', 'pathao-invalid-status-client-id');
    config()->set('couriers.couriers.pathao.client_secret', 'pathao-invalid-status-client-secret');
    config()->set('couriers.couriers.pathao.username', 'pathao-invalid-status@example.com');
    config()->set('couriers.couriers.pathao.password', 'pathao-invalid-status-password');
    config()->set('couriers.couriers.pathao.store_id', '44');
    $shipment = Order::factory()->create()->shipments()->create([
        'courier_code' => 'pathao',
        'consignment_id' => 'PATHAO-CONSIGNMENT-1003',
        'status' => 'pending',
    ]);

    $tracking = app(PathaoCourier::class)->trackShipment($shipment);

    expect($tracking['available'])->toBeFalse()
        ->and($tracking['current_status'])->toBe('pending')
        ->and($tracking['message'])->toContain('no recognizable parcel status');
});

it('creates a Steadfast parcel with the documented request and stores only safe identifiers', function () {
    Http::preventStrayRequests();
    Http::fake([
        'portal.packzy.com/api/v1/create_order' => Http::response([
            'consignment_id' => 'SF-CONSIGNMENT-1001',
            'tracking_code' => 'SF-TRACK-1001',
            'status' => 'in_review',
            'Secret-Key' => 'response-secret-must-not-persist',
            'access_token' => 'response-token-must-not-persist',
        ]),
    ]);
    config()->set('couriers.couriers.steadfast.enabled', true);
    config()->set('couriers.couriers.steadfast.api_url', 'https://portal.packzy.com/api/v1');
    config()->set('couriers.couriers.steadfast.api_key', 'steadfast-test-api-key');
    config()->set('couriers.couriers.steadfast.secret_key', 'steadfast-test-secret-key');

    $order = Order::factory()->create([
        'order_number' => 'ORD-SF-1001',
        'customer_name' => 'Steadfast Customer',
        'phone' => '01710000011',
        'address' => 'House 11, Test Street',
        'area' => 'Dhanmondi',
        'city' => 'Dhaka',
        'payment_method' => 'cod',
        'payment_status' => 'pending',
        'total' => 1060,
        'status' => 'confirmed',
    ]);

    $result = app(CourierManager::class)->createShipment($order, 'steadfast');

    expect($result['success'])->toBeTrue()
        ->and($result['consignment_id'])->toBe('SF-CONSIGNMENT-1001')
        ->and($result['tracking_code'])->toBe('SF-TRACK-1001')
        ->and($result['status'])->toBe('in_review');
    expect($order->fresh()->status)->toBe('processing');

    $shipment = $order->shipments()->firstOrFail();
    expect($shipment->request_payload)->toBe([
        'invoice' => 'ORD-SF-1001',
        'cod_amount' => 1060,
        'payment_method' => 'cod',
    ])->and($shipment->response_payload)->toBe([
        'consignment_id' => 'SF-CONSIGNMENT-1001',
        'tracking_code' => 'SF-TRACK-1001',
        'status' => 'in_review',
    ]);

    $storedMetadata = json_encode([$shipment->request_payload, $shipment->response_payload]);
    expect($storedMetadata)
        ->not->toContain('steadfast-test-api-key')
        ->not->toContain('steadfast-test-secret-key')
        ->not->toContain('response-secret-must-not-persist')
        ->not->toContain('response-token-must-not-persist');
    expect(json_encode($result))
        ->not->toContain('steadfast-test-api-key')
        ->not->toContain('steadfast-test-secret-key');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://portal.packzy.com/api/v1/create_order');
    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Api-Key', 'steadfast-test-api-key')
        && $request->hasHeader('Secret-Key', 'steadfast-test-secret-key'));
    Http::assertSent(fn (Request $request): bool => $request['invoice'] === 'ORD-SF-1001');
    Http::assertSent(fn (Request $request): bool => $request['recipient_name'] === 'Steadfast Customer');
    Http::assertSent(fn (Request $request): bool => $request['recipient_phone'] === '01710000011');
    Http::assertSent(fn (Request $request): bool => $request['recipient_address'] === 'House 11, Test Street, Dhanmondi, Dhaka');
    Http::assertSent(fn (Request $request): bool => is_numeric($request['cod_amount'])
        && (float) $request['cod_amount'] === 1060.0);

    $admin = completeManagementTwoFactorForTest(createSystemOwnerUser());
    $this->actingAs($admin)
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertDontSee('steadfast-test-api-key')
        ->assertDontSee('steadfast-test-secret-key');
});

it('lets an authorized admin send an order through the configured courier selector', function () {
    Http::preventStrayRequests();
    Http::fake([
        'portal.packzy.com/api/v1/create_order' => Http::response([
            'consignment_id' => 'SF-ADMIN-CONSIGNMENT',
            'tracking_code' => 'SF-ADMIN-TRACKING',
            'status' => 'in_review',
        ]),
    ]);
    config()->set('couriers.couriers.steadfast.enabled', true);
    config()->set('couriers.couriers.steadfast.api_url', 'https://portal.packzy.com/api/v1');
    config()->set('couriers.couriers.steadfast.api_key', 'steadfast-test-api-key');
    config()->set('couriers.couriers.steadfast.secret_key', 'steadfast-test-secret-key');
    config()->set('couriers.couriers.pathao.enabled', true);
    config()->set('couriers.couriers.pathao.api_url', 'https://api-hermes.pathao.com');
    config()->set('couriers.couriers.pathao.client_id', 'pathao-admin-client-id');
    config()->set('couriers.couriers.pathao.client_secret', 'pathao-admin-client-secret');
    config()->set('couriers.couriers.pathao.username', 'pathao-admin@example.com');
    config()->set('couriers.couriers.pathao.password', 'pathao-admin-password');
    config()->set('couriers.couriers.pathao.store_id', '45');
    config()->set('couriers.couriers.redx.enabled', true);
    config()->set('couriers.couriers.redx.environment', 'production');
    config()->set('couriers.couriers.redx.api_token', 'redx-admin-token');
    config()->set('couriers.couriers.redx.pickup_store_id', '4');
    config()->set('couriers.couriers.redx.default_weight_grams', 500);
    $admin = completeManagementTwoFactorForTest(createSystemOwnerUser());
    $order = Order::factory()->create([
        'customer_name' => 'Admin Courier Customer',
        'phone' => '01710000021',
        'address' => 'House 21, Test Street',
        'status' => 'confirmed',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertSee('Create Shipment')
        ->assertSee('Pathao')
        ->assertSee('REDX')
        ->assertSee('Steadfast');

    $this->from(route('admin.orders.show', $order))
        ->post(route('admin.orders.shipments.store', $order), ['courier_code' => 'steadfast'])
        ->assertRedirect(route('admin.orders.show', $order));

    $this->assertDatabaseHas('courier_shipments', [
        'order_id' => $order->id,
        'courier_code' => 'steadfast',
        'consignment_id' => 'SF-ADMIN-CONSIGNMENT',
        'tracking_code' => 'SF-ADMIN-TRACKING',
        'status' => 'in_review',
    ]);
    Http::assertSentCount(1);
});

it('keeps a Steadfast booking outcome unknown and blocks blind retries', function () {
    Http::preventStrayRequests();
    Http::fake([
        'portal.packzy.com/api/v1/create_order' => Http::failedConnection(),
    ]);
    config()->set('couriers.couriers.steadfast.enabled', true);
    config()->set('couriers.couriers.steadfast.api_url', 'https://portal.packzy.com/api/v1');
    config()->set('couriers.couriers.steadfast.api_key', 'steadfast-test-api-key');
    config()->set('couriers.couriers.steadfast.secret_key', 'steadfast-test-secret-key');
    $order = Order::factory()->create([
        'customer_name' => 'Steadfast Customer',
        'phone' => '01710000012',
        'address' => 'House 12, Test Street',
        'status' => 'confirmed',
    ]);

    $manager = app(CourierManager::class);
    $firstAttempt = $manager->createShipment($order, 'steadfast');
    $secondAttempt = $manager->createShipment($order, 'steadfast');

    expect($firstAttempt['success'])->toBeFalse()
        ->and($firstAttempt['outcome_unknown'])->toBeTrue()
        ->and($secondAttempt['success'])->toBeFalse()
        ->and($order->shipments()->firstOrFail()->status)->toBe('unknown');
    Http::assertSentCount(1);
});

it('does not log raw courier exception details or secrets', function () {
    Log::spy();
    $steadfast = new class extends SteadfastCourier
    {
        public function isAvailable(): bool
        {
            return true;
        }

        public function createShipment(Order $order): array
        {
            throw new RuntimeException('Secret-Key: exception-secret-must-not-be-logged');
        }
    };
    $order = Order::factory()->create([
        'customer_name' => 'Steadfast Customer',
        'phone' => '01710000019',
        'address' => 'House 19, Test Street',
    ]);

    $result = (new CourierManager($steadfast, new PathaoCourier, new RedxCourier))
        ->createShipment($order, 'steadfast');

    expect($result['outcome_unknown'])->toBeTrue()
        ->and(json_encode($result))->not->toContain('exception-secret-must-not-be-logged');
    Log::shouldHaveReceived('warning')
        ->with('Courier shipment request failed unexpectedly.', [
            'courier_code' => 'steadfast',
            'order_id' => $order->id,
            'exception' => RuntimeException::class,
        ])
        ->once();
});

it('keeps an unrecognized successful Steadfast response unknown and blocks retry', function () {
    Http::preventStrayRequests();
    Http::fake([
        'portal.packzy.com/api/v1/create_order' => Http::response([
            'id' => 'UNVERIFIED-ID-FIELD',
            'tracking' => 'UNVERIFIED-TRACKING-FIELD',
            'status' => 'success',
        ]),
    ]);
    config()->set('couriers.couriers.steadfast.enabled', true);
    config()->set('couriers.couriers.steadfast.api_url', 'https://portal.packzy.com/api/v1');
    config()->set('couriers.couriers.steadfast.api_key', 'steadfast-test-api-key');
    config()->set('couriers.couriers.steadfast.secret_key', 'steadfast-test-secret-key');
    $order = Order::factory()->create([
        'customer_name' => 'Steadfast Customer',
        'phone' => '01710000017',
        'address' => 'House 17, Test Street',
        'status' => 'confirmed',
    ]);

    $manager = app(CourierManager::class);
    $firstAttempt = $manager->createShipment($order, 'steadfast');
    $secondAttempt = $manager->createShipment($order, 'steadfast');
    $shipment = $order->shipments()->firstOrFail();

    expect($firstAttempt['success'])->toBeFalse()
        ->and($firstAttempt['outcome_unknown'])->toBeTrue()
        ->and($secondAttempt['success'])->toBeFalse()
        ->and($shipment->status)->toBe('unknown')
        ->and($shipment->consignment_id)->toBeNull()
        ->and($shipment->tracking_code)->toBeNull();
    Http::assertSentCount(1);
});

it('classifies Steadfast HTTP errors without persisting provider response bodies', function (int $httpStatus, string $shipmentStatus, bool $outcomeUnknown) {
    Http::preventStrayRequests();
    Http::fake([
        'portal.packzy.com/api/v1/create_order' => Http::response([
            'message' => 'Secret-Key: response-secret-must-not-persist',
            'access_token' => 'response-token-must-not-persist',
        ], $httpStatus),
    ]);
    config()->set('couriers.couriers.steadfast.enabled', true);
    config()->set('couriers.couriers.steadfast.api_url', 'https://portal.packzy.com/api/v1');
    config()->set('couriers.couriers.steadfast.api_key', 'steadfast-test-api-key');
    config()->set('couriers.couriers.steadfast.secret_key', 'steadfast-test-secret-key');
    $order = Order::factory()->create([
        'customer_name' => 'Steadfast Customer',
        'phone' => '01710000020',
        'address' => 'House 20, Test Street',
    ]);

    $result = app(CourierManager::class)->createShipment($order, 'steadfast');
    $shipment = $order->shipments()->firstOrFail();

    expect($result['success'])->toBeFalse()
        ->and((bool) ($result['outcome_unknown'] ?? false))->toBe($outcomeUnknown)
        ->and($shipment->status)->toBe($shipmentStatus)
        ->and($shipment->response_payload)->toBe(['http_status' => $httpStatus]);
    expect(json_encode([$result, $shipment->request_payload, $shipment->response_payload]))
        ->not->toContain('steadfast-test-api-key')
        ->not->toContain('steadfast-test-secret-key')
        ->not->toContain('response-secret-must-not-persist')
        ->not->toContain('response-token-must-not-persist');
})->with([
    'bad request' => [400, 'failed', false],
    'authentication failure' => [401, 'failed', false],
    'account forbidden' => [403, 'failed', false],
    'missing endpoint' => [404, 'failed', false],
    'provider validation' => [422, 'failed', false],
    'conflict' => [409, 'unknown', true],
    'rate limited' => [429, 'unknown', true],
    'server error' => [500, 'unknown', true],
]);

it('tracks a Steadfast shipment by consignment ID using documented status lookup', function () {
    Http::preventStrayRequests();
    Http::fake([
        'portal.packzy.com/api/v1/status_by_cid/SF-CONSIGNMENT-1002' => Http::response([
            'status' => 'in_review',
        ]),
    ]);
    config()->set('couriers.couriers.steadfast.enabled', true);
    config()->set('couriers.couriers.steadfast.api_url', 'https://portal.packzy.com/api/v1');
    config()->set('couriers.couriers.steadfast.api_key', 'steadfast-test-api-key');
    config()->set('couriers.couriers.steadfast.secret_key', 'steadfast-test-secret-key');
    $shipment = Order::factory()->create()->shipments()->create([
        'courier_code' => 'steadfast',
        'consignment_id' => 'SF-CONSIGNMENT-1002',
        'tracking_code' => 'SF-TRACK-1002',
        'status' => 'created',
    ]);
    Cache::forget('couriers.steadfast.tracking.'.hash('sha256', 'status_by_cid/SF-CONSIGNMENT-1002'));

    $tracking = app(SteadfastCourier::class)->trackShipment($shipment);

    expect($tracking['available'])->toBeTrue()
        ->and($tracking['current_status'])->toBe('in_review');
    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && $request->url() === 'https://portal.packzy.com/api/v1/status_by_cid/SF-CONSIGNMENT-1002'
        && $request->hasHeader('Api-Key', 'steadfast-test-api-key')
        && $request->hasHeader('Secret-Key', 'steadfast-test-secret-key')
    );
});

it('reconciles an unknown Steadfast shipment by the documented invoice lookup', function () {
    Http::preventStrayRequests();
    Http::fake([
        'portal.packzy.com/api/v1/status_by_invoice/ORD-SF-RECONCILE' => Http::response([
            'consignment_id' => 'SF-CONSIGNMENT-RECONCILED',
            'tracking_code' => 'SF-TRACK-RECONCILED',
            'status' => 'in_review',
        ]),
    ]);
    config()->set('couriers.couriers.steadfast.enabled', true);
    config()->set('couriers.couriers.steadfast.api_url', 'https://portal.packzy.com/api/v1');
    config()->set('couriers.couriers.steadfast.api_key', 'steadfast-test-api-key');
    config()->set('couriers.couriers.steadfast.secret_key', 'steadfast-test-secret-key');
    $order = Order::factory()->create([
        'order_number' => 'ORD-SF-RECONCILE',
        'status' => 'confirmed',
    ]);
    $shipment = $order->shipments()->create([
        'courier_code' => 'steadfast',
        'status' => 'unknown',
    ]);

    $result = app(CourierManager::class)->reconcileShipment($order, 'steadfast');

    expect($result['success'])->toBeTrue()
        ->and($shipment->fresh()->status)->toBe('in_review')
        ->and($shipment->fresh()->consignment_id)->toBe('SF-CONSIGNMENT-RECONCILED')
        ->and($shipment->fresh()->tracking_code)->toBe('SF-TRACK-RECONCILED')
        ->and($order->fresh()->status)->toBe('processing');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && $request->url() === 'https://portal.packzy.com/api/v1/status_by_invoice/ORD-SF-RECONCILE'
        && $request->hasHeader('Api-Key', 'steadfast-test-api-key')
        && $request->hasHeader('Secret-Key', 'steadfast-test-secret-key'));
});

it('shows and processes the admin invoice reconciliation action for an unknown Steadfast shipment', function () {
    Http::preventStrayRequests();
    Http::fake([
        'portal.packzy.com/api/v1/status_by_invoice/ORD-SF-ADMIN-RECONCILE' => Http::response([
            'consignment_id' => 'SF-ADMIN-RECONCILED',
            'tracking_code' => 'SF-ADMIN-RECONCILED-TRACKING',
            'status' => 'in_review',
        ]),
    ]);
    config()->set('couriers.couriers.steadfast.enabled', true);
    config()->set('couriers.couriers.steadfast.api_url', 'https://portal.packzy.com/api/v1');
    config()->set('couriers.couriers.steadfast.api_key', 'steadfast-test-api-key');
    config()->set('couriers.couriers.steadfast.secret_key', 'steadfast-test-secret-key');
    $admin = completeManagementTwoFactorForTest(createSystemOwnerUser());
    $order = Order::factory()->create([
        'order_number' => 'ORD-SF-ADMIN-RECONCILE',
        'status' => 'confirmed',
    ]);
    $order->shipments()->create([
        'courier_code' => 'steadfast',
        'status' => 'unknown',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertSee('Reconcile by invoice');

    $this->from(route('admin.orders.show', $order))
        ->post(route('admin.orders.shipments.reconcile', $order), ['courier_code' => 'steadfast'])
        ->assertRedirect(route('admin.orders.show', $order));

    $this->assertDatabaseHas('courier_shipments', [
        'order_id' => $order->id,
        'courier_code' => 'steadfast',
        'consignment_id' => 'SF-ADMIN-RECONCILED',
        'tracking_code' => 'SF-ADMIN-RECONCILED-TRACKING',
        'status' => 'in_review',
    ]);
});

it('checks Steadfast customer history by phone and derives a success ratio from courier counts', function () {
    Http::preventStrayRequests();
    Http::fake([
        'portal.packzy.com/api/v1/fraud_check/score/01710000013' => Http::response([
            'status' => 'success',
            'data' => [
                'total_parcel' => 10,
                'total_delivered' => 8,
                'total_returned' => 2,
                'success_ratio' => 80,
            ],
        ]),
    ]);
    config()->set('couriers.couriers.steadfast.enabled', true);
    config()->set('couriers.couriers.steadfast.api_url', 'https://portal.packzy.com/api/v1');
    config()->set('couriers.couriers.steadfast.api_key', 'steadfast-history-test-key');
    config()->set('couriers.couriers.steadfast.secret_key', 'steadfast-history-test-secret');

    $history = app(CourierManager::class)->customerHistory('steadfast', '01710000013');

    expect($history['available'])->toBeTrue()
        ->and($history['phone'])->toBe('01710000013')
        ->and($history['total'])->toBe(10)
        ->and($history['delivered'])->toBe(8)
        ->and($history['returned'])->toBe(2)
        ->and($history['success_ratio'])->toBe(80.0);

    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && $request->url() === 'https://portal.packzy.com/api/v1/fraud_check/score/01710000013'
        && $request->hasHeader('Api-Key', 'steadfast-history-test-key')
        && $request->hasHeader('Secret-Key', 'steadfast-history-test-secret'));
});

it('shows the Steadfast delivery ratio on the order page before a shipment is accepted', function () {
    Http::preventStrayRequests();
    Http::fake([
        'portal.packzy.com/api/v1/fraud_check/score/01710000022' => Http::response([
            'status' => 'success',
            'data' => [
                'total_parcel' => 10,
                'total_delivered' => 8,
                'total_cancelled' => 2,
                'success_ratio' => 80,
                'score' => 73,
            ],
        ]),
    ]);
    config()->set('couriers.couriers.steadfast.enabled', true);
    config()->set('couriers.couriers.steadfast.api_url', 'https://portal.packzy.com/api/v1');
    config()->set('couriers.couriers.steadfast.api_key', 'steadfast-dashboard-test-key');
    config()->set('couriers.couriers.steadfast.secret_key', 'steadfast-dashboard-test-secret');
    $admin = completeManagementTwoFactorForTest(createSystemOwnerUser());
    $order = Order::factory()->create([
        'phone' => '01710000022',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)
        ->from(route('admin.orders.show', $order))
        ->post(route('admin.orders.courier-history', $order), ['courier_code' => 'steadfast'])
        ->assertRedirect(route('admin.orders.show', $order));

    expect($response->getSession()->get('courierHistory'))->toMatchArray([
        'available' => true,
        'total' => 10,
        'delivered' => 8,
        'success_ratio' => 80.0,
    ]);

    $this->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertSee('Total courier parcels: 10')
        ->assertSee('Delivered parcels: 8')
        ->assertSee('Success ratio: 80.00%')
        ->assertDontSee('Official courier score')
        ->assertDontSee('Total cancelled parcels');

    Http::assertSentCount(1);
});

it('shows provider-specific history unavailability without making unsupported API calls', function (string $courierCode, string $expectedMessage) {
    Http::preventStrayRequests();
    config()->set('couriers.couriers.steadfast.enabled', false);
    config()->set('couriers.couriers.steadfast.api_key', null);
    config()->set('couriers.couriers.steadfast.secret_key', null);
    $admin = completeManagementTwoFactorForTest(createSystemOwnerUser());
    $order = Order::factory()->create(['phone' => '01710000014']);

    $this->actingAs($admin)
        ->from(route('admin.orders.show', $order))
        ->post(route('admin.orders.courier-history', $order), ['courier_code' => $courierCode])
        ->assertRedirect(route('admin.orders.show', $order))
        ->assertSessionHas('courierHistory', fn (array $history): bool => ! $history['available']
            && $history['courier_code'] === $courierCode
            && $history['phone'] === '01710000014'
            && str_contains($history['message'], $expectedMessage));

    $this->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertSee('Customer Courier History')
        ->assertSee($expectedMessage);

    Http::assertNothingSent();
})->with([
    'redx' => ['redx', 'REDX API'],
    'pathao' => ['pathao', 'Pathao API'],
    'steadfast' => ['steadfast', 'not active or is missing valid merchant API credentials'],
]);

it('renders manual Pathao and REDX fraud-check links with the order phone', function () {
    $admin = completeManagementTwoFactorForTest(createSystemOwnerUser());
    $order = Order::factory()->create(['phone' => '01710000014']);

    $this->actingAs($admin)
        ->get(route('admin.orders.show', $order))
        ->assertSee('Copy number & open Pathao')
        ->assertSee('Copy number & open REDX')
        ->assertSee('href="https://merchant.pathao.com/"', false)
        ->assertSee('href="https://redx.com.bd/login/"', false)
        ->assertSee('data-courier-name="Pathao" data-customer-phone="01710000014"', false)
        ->assertSee('data-courier-name="REDX" data-customer-phone="01710000014"', false);
});

it('returns a safe customer-history error when a supported provider call throws', function () {
    Log::spy();
    $steadfast = new class extends SteadfastCourier
    {
        public function supportsCustomerHistory(): bool
        {
            return true;
        }

        public function getCustomerHistory(string $phone): array
        {
            throw new RuntimeException('Request failed with Secret-Key: sensitive-test-secret');
        }
    };

    $history = (new CourierManager($steadfast, new PathaoCourier, new RedxCourier))
        ->customerHistory('steadfast', '01710000018');

    expect($history['available'])->toBeFalse()
        ->and($history['error'])->toBeTrue()
        ->and($history['message'])->toBe('Unable to retrieve customer delivery history right now.')
        ->and(json_encode($history))->not->toContain('sensitive-test-secret');

    Log::shouldHaveReceived('warning')
        ->with('Courier customer-history lookup failed.', [
            'courier_code' => 'steadfast',
            'exception' => RuntimeException::class,
        ])
        ->once();
});

it('does not permit guests to check customer courier history', function () {
    $order = Order::factory()->create();

    $this->post(route('admin.orders.courier-history', $order), ['courier_code' => 'redx'])
        ->assertRedirect(route('login'));
});

it('calculates customer success ratio only when the courier provides total and delivered counts', function () {
    $steadfast = new class extends SteadfastCourier
    {
        public function supportsCustomerHistory(): bool
        {
            return true;
        }

        public function getCustomerHistory(string $phone): array
        {
            return [
                'available' => true,
                'total' => 10,
                'delivered' => 8,
                'returned' => 2,
                'cancelled' => null,
                'failed' => null,
                'success_ratio' => null,
                'official_score' => null,
                'message' => null,
            ];
        }
    };

    $manager = new CourierManager($steadfast, new PathaoCourier, new RedxCourier);
    $history = $manager->customerHistory('steadfast', '01710000015');

    expect($history['total'])->toBe(10)
        ->and($history['delivered'])->toBe(8)
        ->and($history['success_ratio'])->toBe(80.0);
});

it('does not derive a success ratio from an official score alone', function () {
    $steadfast = new class extends SteadfastCourier
    {
        public function supportsCustomerHistory(): bool
        {
            return true;
        }

        public function getCustomerHistory(string $phone): array
        {
            return [
                'available' => true,
                'total' => null,
                'delivered' => null,
                'returned' => null,
                'cancelled' => null,
                'failed' => null,
                'success_ratio' => null,
                'official_score' => 73,
                'message' => null,
            ];
        }
    };

    $manager = new CourierManager($steadfast, new PathaoCourier, new RedxCourier);
    $history = $manager->customerHistory('steadfast', '01710000016');

    expect($history['official_score'])->toBe(73.0)
        ->and($history['success_ratio'])->toBeNull();
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
