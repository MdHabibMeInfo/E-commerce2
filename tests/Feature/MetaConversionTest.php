<?php

use App\Jobs\SendMetaPurchaseEvent;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

it('dispatches CAPI Purchase only after marketing consent is granted', function (?string $marketingConsent, bool $shouldDispatch) {
    Queue::fake();
    config()->set([
        'services.meta.pixel_id' => '123456789012345',
        'services.meta.capi_enabled' => true,
        'services.meta.capi_access_token' => 'test-access-token',
    ]);

    $product = Product::create([
        'name' => 'Meta Test Product',
        'slug' => 'meta-test-product',
        'price' => 420,
        'stock' => 10,
        'type' => 'simple',
        'is_active' => true,
    ]);
    $cart = Cart::query()->create(['session_id' => session()->getId()]);
    session()->put('cart_id', $cart->id);
    $cart->items()->create([
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => $product->price,
        'quantity' => 1,
    ]);

    $checkout = $this->followingRedirects();

    if ($marketingConsent !== null) {
        $checkout->withUnencryptedCookie('meta_marketing_consent', $marketingConsent)
            ->withUnencryptedCookie('_fbp', 'fb.1.1728000000000.1234567890')
            ->withUnencryptedCookie('_fbc', 'fb.1.1728000000000.test-click-id');
    }

    $response = $checkout->post(route('checkout.store'), [
        'customer_name' => 'Meta Test Customer',
        'phone' => '01712345678',
        'address' => 'House 12, Test Street',
        'city' => 'Dhaka',
        'payment_method' => 'cod',
    ]);
    $order = Order::query()->where('customer_name', 'Meta Test Customer')->firstOrFail();

    $response->assertOk()
        ->assertSee('data-meta-pixel-id="123456789012345"', false)
        ->assertSee('data-meta-consent-banner', false)
        ->assertSee('window.metaPurchaseEvent =', false)
        ->assertSee('purchase-order-'.$order->id);

    if ($shouldDispatch) {
        Queue::assertPushed(SendMetaPurchaseEvent::class, fn (SendMetaPurchaseEvent $job): bool => $job->orderId === $order->id
            && $job->fbp === 'fb.1.1728000000000.1234567890'
            && $job->fbc === 'fb.1.1728000000000.test-click-id'
            && str_ends_with($job->eventSourceUrl, '/checkout/success/'.$order->id));
    } else {
        Queue::assertNotPushed(SendMetaPurchaseEvent::class);
    }
})->with([
    'consented' => ['granted', true],
    'rejected' => ['denied', false],
    'no decision' => [null, false],
]);

it('sends a deduplicated CAPI Purchase with hashed phone and browser match data', function () {
    Http::preventStrayRequests();
    Http::fake([
        'graph.facebook.com/*' => Http::response(['events_received' => 1, 'fbtrace_id' => 'test-trace'], 200),
    ]);
    config()->set([
        'services.meta.pixel_id' => '123456789012345',
        'services.meta.capi_enabled' => true,
        'services.meta.capi_access_token' => 'test-access-token',
        'services.meta.graph_api_version' => 'v25.0',
        'services.meta.currency' => 'BDT',
    ]);

    $order = Order::factory()->create([
        'phone' => '01712345678',
        'subtotal' => 420,
        'delivery_fee' => 60,
        'total' => 480,
    ]);
    $order->items()->create([
        'name' => 'Green Beans',
        'price' => 420,
        'quantity' => 1,
    ]);

    (new SendMetaPurchaseEvent(
        orderId: $order->id,
        eventSourceUrl: 'https://shop.example/checkout/success/'.$order->id,
        clientIpAddress: '203.0.113.10',
        clientUserAgent: 'Meta CAPI test agent',
        fbp: 'fb.1.1728000000000.1234567890',
        fbc: 'fb.1.1728000000000.test-click-id',
    ))->handle();

    Http::assertSent(function (Request $request) use ($order): bool {
        $events = json_decode($request['data'], true, flags: JSON_THROW_ON_ERROR);
        $event = $events[0];

        return $request->url() === 'https://graph.facebook.com/v25.0/123456789012345/events'
            && $request['access_token'] === 'test-access-token'
            && $event['event_name'] === 'Purchase'
            && $event['event_id'] === 'purchase-order-'.$order->id
            && $event['event_source_url'] === 'https://shop.example/checkout/success/'.$order->id
            && $event['action_source'] === 'website'
            && $event['user_data']['ph'] === [hash('sha256', '8801712345678')]
            && $event['user_data']['fbp'] === 'fb.1.1728000000000.1234567890'
            && $event['user_data']['fbc'] === 'fb.1.1728000000000.test-click-id'
            && $event['custom_data']['currency'] === 'BDT'
            && (float) $event['custom_data']['value'] === 480.0
            && ! str_contains($request['data'], '01712345678');
    });
});

it('retries Meta rate-limit responses through the queue', function () {
    Http::preventStrayRequests();
    Http::fake([
        'graph.facebook.com/*' => Http::response(['error' => ['code' => 4]], 429),
    ]);
    config()->set([
        'services.meta.pixel_id' => '123456789012345',
        'services.meta.capi_enabled' => true,
        'services.meta.capi_access_token' => 'test-access-token',
        'services.meta.graph_api_version' => 'v25.0',
    ]);
    $order = Order::factory()->create(['phone' => '01712345678']);
    $job = new SendMetaPurchaseEvent(
        orderId: $order->id,
        eventSourceUrl: 'https://shop.example/checkout/success/'.$order->id,
        clientIpAddress: null,
        clientUserAgent: null,
        fbp: null,
        fbc: null,
    );

    expect(fn () => $job->handle())
        ->toThrow(RuntimeException::class, 'temporarily failed with HTTP 429');
});

it('skips CAPI when no valid matching identifier is available', function () {
    Http::preventStrayRequests();
    Http::fake();
    config()->set([
        'services.meta.pixel_id' => '123456789012345',
        'services.meta.capi_enabled' => true,
        'services.meta.capi_access_token' => 'test-access-token',
        'services.meta.graph_api_version' => 'v25.0',
    ]);
    $order = Order::factory()->create(['phone' => 'not-a-phone']);

    (new SendMetaPurchaseEvent(
        orderId: $order->id,
        eventSourceUrl: 'https://shop.example/checkout/success/'.$order->id,
        clientIpAddress: null,
        clientUserAgent: null,
        fbp: null,
        fbc: null,
    ))->handle();

    Http::assertNothingSent();
});
