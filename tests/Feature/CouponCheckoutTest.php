<?php

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CouponService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('applies a capped percentage coupon once per guest phone and records the final order discount', function () {
    config([
        'storefront.delivery_fee_inside_dhaka' => 0,
        'storefront.delivery_fee_outside_dhaka' => 0,
    ]);
    $coupon = Coupon::query()->create([
        'code' => 'WELCOME25',
        'discount_type' => 'percentage',
        'discount_value' => 25,
        'minimum_subtotal' => 100,
        'maximum_discount' => 15,
        'usage_limit' => 2,
        'per_customer_limit' => 1,
        'is_active' => true,
    ]);
    $product = Product::create([
        'name' => 'Coupon Product',
        'slug' => 'coupon-product',
        'price' => 100,
        'stock' => 10,
        'type' => 'simple',
        'is_active' => true,
    ]);
    $cart = Cart::query()->create(['session_id' => session()->getId()]);
    $cart->items()->create([
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => $product->price,
        'quantity' => 1,
    ]);
    session()->put('cart_id', $cart->id);

    $this->post(route('checkout.coupon.apply'), [
        'coupon_code' => 'welcome25',
        'phone' => '01710000021',
    ])->assertRedirect(route('checkout'));

    $this->get(route('checkout'))
        ->assertOk()
        ->assertSee('WELCOME25')
        ->assertSee('15.00');

    $this->post(route('checkout.store'), [
        'customer_name' => 'Coupon Guest',
        'phone' => '01710000021',
        'address' => 'House 21, Test Street',
        'city' => 'Dhaka',
        'payment_method' => 'cod',
        'coupon_code' => 'WELCOME25',
    ])->assertRedirect();

    $order = Order::query()->where('phone', '01710000021')->firstOrFail();
    expect((float) $order->discount)->toBe(15.0)
        ->and((float) $order->total)->toBe(85.0)
        ->and($order->coupon_id)->toBe($coupon->id)
        ->and($order->coupon_code)->toBe('WELCOME25');
    $this->assertDatabaseHas('coupon_redemptions', [
        'coupon_id' => $coupon->id,
        'order_id' => $order->id,
        'discount' => 15,
    ]);

    $secondCart = Cart::query()->create(['session_id' => session()->getId()]);
    $secondCart->items()->create([
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => $product->price,
        'quantity' => 1,
    ]);
    session()->put('cart_id', $secondCart->id);

    $this->post(route('checkout.coupon.apply'), [
        'coupon_code' => 'WELCOME25',
        'phone' => '01710000021',
    ])->assertSessionHasErrors('coupon_code');

    expect(CouponRedemption::query()->where('coupon_id', $coupon->id)->count())->toBe(1);
});

it('discounts only the products targeted by a coupon and records that amount on the order', function () {
    config([
        'storefront.delivery_fee_inside_dhaka' => 0,
        'storefront.delivery_fee_outside_dhaka' => 0,
    ]);
    $target = Product::create([
        'name' => 'Targeted product',
        'slug' => 'targeted-product',
        'price' => 100,
        'stock' => 10,
        'type' => 'simple',
        'is_active' => true,
    ]);
    $other = Product::create([
        'name' => 'Other product',
        'slug' => 'other-product',
        'price' => 100,
        'stock' => 10,
        'type' => 'simple',
        'is_active' => true,
    ]);
    $coupon = Coupon::query()->create([
        'code' => 'TARGET20',
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'minimum_subtotal' => 0,
        'target_type' => 'product',
        'target_id' => $target->id,
        'is_active' => true,
    ]);
    $cart = Cart::query()->create(['session_id' => session()->getId()]);
    foreach ([$target, $other] as $product) {
        $cart->items()->create([
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
        ]);
    }
    session()->put('cart_id', $cart->id);

    $this->post(route('checkout.coupon.apply'), [
        'coupon_code' => $coupon->code,
        'phone' => '01710000031',
    ])->assertRedirect(route('checkout'));

    $this->get(route('checkout'))->assertSee('20.00');
    $this->post(route('checkout.store'), [
        'customer_name' => 'Targeted Guest',
        'phone' => '01710000031',
        'address' => 'House 31, Test Street',
        'city' => 'Dhaka',
        'payment_method' => 'cod',
        'coupon_code' => 'TARGET20',
    ])->assertRedirect();

    $order = Order::query()->where('phone', '01710000031')->firstOrFail();
    expect((float) $order->discount)->toBe(20.0)
        ->and((float) $order->total)->toBe(180.0);
});

it('limits category and variant campaigns to matching cart lines', function (string $targetType, int $targetId, array $matchingLine) {
    $coupon = Coupon::query()->create([
        'code' => strtoupper($targetType).'-10',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'target_type' => $targetType,
        'target_id' => $targetId,
        'is_active' => true,
    ]);
    $service = app(CouponService::class);
    $customerKey = $service->customerKey(null, '01710000032');
    $lines = [
        $matchingLine + ['product_id' => 11, 'category_id' => 7, 'product_variant_id' => 13, 'amount' => 100],
        ['product_id' => 12, 'category_id' => 8, 'product_variant_id' => 14, 'amount' => 100],
    ];

    $result = $service->calculateForCart($coupon->code, $lines, 200, 60, $customerKey);

    expect($result['discount'])->toBe(10.0)
        ->and($result['delivery_discount'])->toBe(0.0);
})->with([
    'category scope' => ['category', 7, []],
    'variant scope' => ['variant', 13, []],
]);

it('applies an automatic minimum-spend free-delivery offer to the final order', function () {
    config([
        'storefront.delivery_fee_inside_dhaka' => 60,
        'storefront.delivery_fee_outside_dhaka' => 100,
    ]);
    $campaign = Coupon::query()->create([
        'code' => null,
        'is_automatic' => true,
        'discount_type' => 'free_shipping',
        'discount_value' => 0,
        'minimum_subtotal' => 150,
        'is_active' => true,
    ]);
    $product = Product::create([
        'name' => 'Free delivery product',
        'slug' => 'free-delivery-product',
        'price' => 150,
        'stock' => 10,
        'type' => 'simple',
        'is_active' => true,
    ]);
    $cart = Cart::query()->create(['session_id' => session()->getId()]);
    $cart->items()->create([
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => $product->price,
        'quantity' => 1,
    ]);
    session()->put('cart_id', $cart->id);

    $this->withSession(['_old_input' => ['city' => 'Dhaka']]);
    $this->get(route('checkout'))
        ->assertSee('Automatic offer applied')
        ->assertSee('Delivery offer')
        ->assertSee('-৳60.00');
    $this->post(route('checkout.store'), [
        'customer_name' => 'Free Delivery Guest',
        'phone' => '01710000033',
        'address' => 'House 33, Test Street',
        'city' => 'Dhaka',
        'payment_method' => 'cod',
    ])->assertRedirect();

    $order = Order::query()->where('phone', '01710000033')->firstOrFail();
    expect((float) $order->delivery_fee)->toBe(0.0)
        ->and((float) $order->delivery_discount)->toBe(60.0)
        ->and((float) $order->discount)->toBe(0.0)
        ->and((float) $order->total)->toBe(150.0)
        ->and($order->coupon_id)->toBe($campaign->id)
        ->and($order->coupon_code)->toBeNull();
    $this->assertDatabaseHas('coupon_redemptions', [
        'coupon_id' => $campaign->id,
        'order_id' => $order->id,
        'delivery_discount' => 60,
    ]);
});

it('requires checkout totals to be reviewed when an automatic offer expires before order placement', function () {
    $campaign = Coupon::query()->create([
        'code' => null,
        'is_automatic' => true,
        'discount_type' => 'fixed',
        'discount_value' => 10,
        'minimum_subtotal' => 0,
        'is_active' => true,
    ]);
    $product = Product::create([
        'name' => 'Changing offer product',
        'slug' => 'changing-offer-product',
        'price' => 100,
        'stock' => 10,
        'type' => 'simple',
        'is_active' => true,
    ]);
    $cart = Cart::query()->create(['session_id' => session()->getId()]);
    $cart->items()->create([
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => $product->price,
        'quantity' => 1,
    ]);
    session()->put('cart_id', $cart->id);

    $this->get(route('checkout'))->assertSee('Automatic offer applied');
    $campaign->update(['is_active' => false]);

    $this->post(route('checkout.store'), [
        'customer_name' => 'Changing Offer Guest',
        'phone' => '01710000034',
        'address' => 'House 34, Test Street',
        'city' => 'Dhaka',
        'payment_method' => 'cod',
    ])->assertSessionHasErrors('coupon_code');

    $this->assertDatabaseCount('orders', 0);
    expect($cart->items()->count())->toBe(1);
});

it('creates an automatic product-scoped free-delivery campaign without a code', function () {
    $owner = completeManagementTwoFactorForTest(createSystemOwnerUser());
    $product = Product::create([
        'name' => 'Campaign target',
        'slug' => 'campaign-target',
        'price' => 200,
        'stock' => 10,
        'type' => 'simple',
        'is_active' => true,
    ]);

    $this->actingAs($owner)
        ->post(route('admin.coupons.store'), [
            'is_automatic' => true,
            'target' => 'product:'.$product->id,
            'discount_type' => 'free_shipping',
            'discount_value' => 0,
            'minimum_subtotal' => 500,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.coupons.index'));

    $campaign = Coupon::query()->where('is_automatic', true)->firstOrFail();
    expect($campaign->code)->toBeNull()
        ->and($campaign->target_type)->toBe('product')
        ->and($campaign->target_id)->toBe($product->id)
        ->and($campaign->discount_type)->toBe('free_shipping');

    $this->get(route('admin.coupons.index'))
        ->assertOk()
        ->assertSee('Automatic offer')
        ->assertSee('Free delivery')
        ->assertSee('Product: Campaign target');
});

it('returns catalog targets only to coupon managers', function () {
    $owner = completeManagementTwoFactorForTest(createSystemOwnerUser());
    $product = Product::create([
        'name' => 'Searchable promotion target',
        'slug' => 'searchable-promotion-target',
        'price' => 200,
        'stock' => 10,
        'type' => 'simple',
        'is_active' => true,
    ]);

    $this->actingAs($owner)
        ->get(route('admin.coupons.targets', ['search' => 'Searchable promotion']))
        ->assertOk()
        ->assertJsonFragment([
            'value' => 'product:'.$product->id,
            'label' => $product->name,
            'type' => 'Product',
        ]);
});

it('clears stale invalid coupon data from the checkout session', function () {
    session()->put('checkout.coupon_code', 'EXPIRED');
    session()->put('checkout.coupon_customer_key', 'stale-hash');

    Coupon::query()->create([
        'code' => 'EXPIRED',
        'discount_type' => 'fixed',
        'discount_value' => 10,
        'minimum_subtotal' => 0,
        'expires_at' => now()->subDay(),
        'is_active' => true,
    ]);

    $this->get(route('checkout'))
        ->assertOk()
        ->assertDontSee('EXPIRED');

    expect(session('checkout.coupon_code'))->toBeNull()
        ->and(session('checkout.coupon_customer_key'))->toBeNull();
});

it('does not repopulate an invalid coupon code in the checkout form after redirect', function () {
    Coupon::query()->create([
        'code' => 'FAILED-10',
        'discount_type' => 'fixed',
        'discount_value' => 10,
        'minimum_subtotal' => 100,
        'expires_at' => now()->subDay(),
        'is_active' => true,
    ]);

    $this->from(route('checkout'))
        ->post(route('checkout.coupon.apply'), [
            'coupon_code' => 'FAILED-10',
            'phone' => '01710000023',
        ])
        ->assertSessionHasErrors('coupon_code');

    $this->get(route('checkout'))
        ->assertOk()
        ->assertDontSee('FAILED-10');
});

it('uses Bangladesh time for coupon validity checks', function () {
    expect(config('app.timezone'))->toBe('Asia/Dhaka');

    $coupon = Coupon::query()->create([
        'code' => 'BD-TODAY',
        'discount_type' => 'fixed',
        'discount_value' => 25,
        'minimum_subtotal' => 0,
        'starts_at' => now()->setTimezone('Asia/Dhaka'),
        'expires_at' => now()->setTimezone('Asia/Dhaka')->addDay(),
        'is_active' => true,
    ]);

    $service = app(CouponService::class);
    $result = $service->calculate($coupon->code, 1000, hash_hmac('sha256', 'phone:01710000024', config('app.key')));

    expect($result['discount'])->toBe(25.0)
        ->and($result['coupon']->code)->toBe('BD-TODAY');
});

it('rejects expired, inactive, and below-minimum coupons', function (array $couponData, string $message) {
    $coupon = Coupon::query()->create(array_merge([
        'code' => 'RULETEST',
        'discount_type' => 'fixed',
        'discount_value' => 10,
        'minimum_subtotal' => 100,
        'is_active' => true,
    ], $couponData));

    $this->post(route('checkout.coupon.apply'), [
        'coupon_code' => $coupon->code,
        'phone' => '01710000022',
    ])->assertRedirect();

    $product = Product::create([
        'name' => 'Coupon Minimum Product',
        'slug' => 'coupon-minimum-product',
        'price' => 50,
        'stock' => 5,
        'type' => 'simple',
        'is_active' => true,
    ]);
    $cart = Cart::query()->create(['session_id' => session()->getId()]);
    $cart->items()->create([
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => $product->price,
        'quantity' => 1,
    ]);
    session()->put('cart_id', $cart->id);

    $this->post(route('checkout.coupon.apply'), [
        'coupon_code' => $coupon->code,
        'phone' => '01710000022',
    ])->assertSessionHasErrors('coupon_code');

    expect($message)->not->toBe('');
})->with([
    'minimum subtotal' => [['minimum_subtotal' => 100], 'minimum'],
    'inactive campaign' => [['is_active' => false, 'minimum_subtotal' => 0], 'unavailable'],
    'expired campaign' => [['expires_at' => now()->subMinute(), 'minimum_subtotal' => 0], 'not currently valid'],
]);

it('allows the system owner to create and deactivate a coupon campaign', function () {
    $owner = completeManagementTwoFactorForTest(createSystemOwnerUser());

    $this->actingAs($owner)
        ->post(route('admin.coupons.store'), [
            'code' => 'spring-10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'minimum_subtotal' => 200,
            'maximum_discount' => 50,
            'usage_limit' => 100,
            'per_customer_limit' => 1,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.coupons.index'));

    $coupon = Coupon::query()->where('code', 'SPRING-10')->firstOrFail();
    expect($coupon->created_by)->toBe($owner->id);

    $this->patch(route('admin.coupons.status.update', $coupon), ['is_active' => false])
        ->assertRedirect(route('admin.coupons.index'));

    expect($coupon->fresh()->is_active)->toBeFalse();
    $this->assertDatabaseHas('security_events', [
        'user_id' => $owner->id,
        'action' => 'coupon_deactivated',
    ]);
});

it('searches promotions by coupon code', function () {
    $owner = completeManagementTwoFactorForTest(createSystemOwnerUser());
    Coupon::query()->create([
        'code' => 'SPRING10',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'is_active' => true,
    ]);
    Coupon::query()->create([
        'code' => 'CATEGORY-DEAL',
        'target_type' => 'category',
        'discount_type' => 'fixed',
        'discount_value' => 20,
        'is_active' => true,
    ]);

    $response = $this->actingAs($owner)
        ->get(route('admin.coupons.index', ['search' => 'SPRING']))
        ->assertOk()
        ->assertSee('SPRING10')
        ->assertSee('Promotions')
        ->assertDontSee('CATEGORY-DEAL');

    $page = $response->getContent();
    expect(strpos($page, 'Create promotion'))
        ->toBeLessThan(strpos($page, 'id="coupon-search"'))
        ->and(strpos($page, 'id="coupon-search"'))
        ->toBeLessThan(strpos($page, '<table'));

});

it('filters promotions by promotion type, discount type, target, and status', function () {
    $owner = completeManagementTwoFactorForTest(createSystemOwnerUser());
    Coupon::query()->create([
        'code' => 'FIXED-COUPON',
        'discount_type' => 'fixed',
        'discount_value' => 20,
        'is_active' => true,
    ]);
    Coupon::query()->create([
        'code' => null,
        'is_automatic' => true,
        'target_type' => 'category',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'is_active' => false,
    ]);

    $this->actingAs($owner)
        ->get(route('admin.coupons.index', ['promotion_type' => 'automatic']))
        ->assertOk()
        ->assertSee('1 promotions')
        ->assertDontSee('FIXED-COUPON');

    $this->get(route('admin.coupons.index', ['discount_type' => 'fixed', 'target_type' => 'all', 'status' => 'active']))
        ->assertOk()
        ->assertSee('FIXED-COUPON')
        ->assertSee('1 promotions');

    $this->get(route('admin.coupons.index', ['target_type' => 'category']))
        ->assertOk()
        ->assertSee('1 promotions')
        ->assertDontSee('FIXED-COUPON');

    $this->get(route('admin.coupons.index'))
        ->assertOk()
        ->assertSee('Clear')
        ->assertSee('FIXED-COUPON')
        ->assertSee('2 promotions');
});

it('requires coupon permissions to manage campaigns', function () {
    $role = Role::create(['name' => 'Catalog Staff', 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::findOrCreate('products.view', 'web'));
    $staff = User::factory()->create(['account_type' => 'admin']);
    $staff->assignRole($role);

    $this->actingAs(completeManagementTwoFactorForTest($staff))
        ->get(route('admin.coupons.index'))
        ->assertForbidden();
});

it('locks coupon rules after the first redemption', function () {
    $owner = completeManagementTwoFactorForTest(createSystemOwnerUser());
    $coupon = Coupon::query()->create([
        'code' => 'USED-CODE',
        'discount_type' => 'fixed',
        'discount_value' => 10,
        'is_active' => true,
    ]);
    $order = Order::factory()->create(['coupon_id' => $coupon->id, 'coupon_code' => $coupon->code]);
    CouponRedemption::query()->create([
        'coupon_id' => $coupon->id,
        'order_id' => $order->id,
        'customer_key' => hash('sha256', 'customer'),
        'discount' => 10,
        'redeemed_at' => now(),
    ]);

    $this->actingAs($owner)
        ->put(route('admin.coupons.update', $coupon), [
            'code' => 'USED-CODE',
            'discount_type' => 'fixed',
            'discount_value' => 20,
            'minimum_subtotal' => 0,
            'is_active' => true,
        ])
        ->assertConflict();
});
