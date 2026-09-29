<?php

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;

it('loads the home page', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

it('shows product names and current and compare-at prices on home and shop cards', function () {
    $category = Category::create([
        'name' => 'Fresh Fruits',
        'slug' => 'fresh-fruits',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    Product::create([
        'category_id' => $category->id,
        'name' => 'Fresh Mango',
        'slug' => 'fresh-mango',
        'sku' => 'MANGO-CARD-001',
        'short_description' => 'Sweet and juicy mango.',
        'price' => 240.00,
        'compare_at_price' => 299.00,
        'stock' => 20,
        'type' => 'simple',
        'is_active' => true,
    ]);

    foreach ([route('home'), route('shop')] as $url) {
        $response = $this->get($url);

        $response->assertSee('Fresh Mango');
        $response->assertSee('240.00');
        $response->assertSee('299.00');
        $response->assertDontSee('Sweet and juicy mango.');
    }
});

it('loads the shop page with products', function () {
    $category = Category::create([
        'name' => 'Fresh Fruits',
        'slug' => 'fresh-fruits',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    Product::create([
        'category_id' => $category->id,
        'name' => 'Fresh Mango',
        'slug' => 'fresh-mango',
        'sku' => 'MANGO-001',
        'short_description' => 'Sweet and juicy mango.',
        'price' => 240.00,
        'compare_at_price' => 299.00,
        'stock' => 20,
        'type' => 'simple',
        'is_active' => true,
    ]);

    $response = $this->get(route('shop'));

    $response->assertOk();
    $response->assertSee('Fresh Mango');
    $response->assertSee('data-open-cart');
    $response->assertSee('data-product-card');
    $response->assertSee('data-product-name');
    $response->assertSee('Fresh Mango');
    $response->assertSee('Default Sorting');
    $response->assertSee('Sort by Latest');
    $response->assertSee('Sort by Oldest');
    $response->assertSee('Price Low to High');
    $response->assertSee('Price High to Low');
});

it('sorts shop products according to the selected order', function (?string $sort, array $expectedNames) {
    $category = Category::create([
        'name' => 'Fresh Fruits',
        'slug' => 'fresh-fruits',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    foreach ([
        ['name' => 'Old Product', 'sku' => 'SORT-001', 'price' => 200.00],
        ['name' => 'Middle Product', 'sku' => 'SORT-002', 'price' => 500.00],
        ['name' => 'New Product', 'sku' => 'SORT-003', 'price' => 100.00],
    ] as $productData) {
        Product::create([
            'category_id' => $category->id,
            'name' => $productData['name'],
            'slug' => str($productData['name'])->slug(),
            'sku' => $productData['sku'],
            'price' => $productData['price'],
            'stock' => 20,
            'type' => 'simple',
            'is_active' => true,
        ]);
    }

    $url = $sort === null ? route('shop') : route('shop', ['sort' => $sort]);
    $response = $this->get($url);

    $response->assertSeeTextInOrder($expectedNames);
})->with([
    'default sorting' => [null, ['New Product', 'Middle Product', 'Old Product']],
    'latest first' => ['latest', ['New Product', 'Middle Product', 'Old Product']],
    'oldest first' => ['oldest', ['Old Product', 'Middle Product', 'New Product']],
    'price low to high' => ['price_asc', ['New Product', 'Old Product', 'Middle Product']],
    'price high to low' => ['price_desc', ['Middle Product', 'Old Product', 'New Product']],
]);

it('lets shoppers choose how many products appear per page', function () {
    $category = Category::create([
        'name' => 'Fresh Fruits',
        'slug' => 'fresh-fruits',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    foreach (range(1, 25) as $number) {
        Product::create([
            'category_id' => $category->id,
            'name' => "Shop Product {$number}",
            'slug' => "shop-product-{$number}",
            'sku' => sprintf('PAGE-%03d', $number),
            'price' => 100 + $number,
            'stock' => 20,
            'type' => 'simple',
            'is_active' => true,
        ]);
    }

    $defaultResponse = $this->get(route('shop'));
    $selectedResponse = $this->get(route('shop', ['per_page' => 20]));

    expect($defaultResponse->viewData('products')->count())->toBe(16)
        ->and($selectedResponse->viewData('products')->count())->toBe(20);

    $selectedResponse->assertSee('Show 16');
    $selectedResponse->assertSee('Show 20');
    $selectedResponse->assertSee('Show 24');
    $selectedResponse->assertSee('Show 36');
    $selectedResponse->assertSee('per_page=20', false);
    $selectedResponse->assertSee('page=2', false);
});

it('shows the shop-by category, price, and product-variant filters on the shop page', function () {
    $category = Category::create([
        'name' => 'Fresh Fruits',
        'slug' => 'fresh-fruits',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    Product::create([
        'category_id' => $category->id,
        'name' => 'Fresh Mango',
        'slug' => 'fresh-mango',
        'sku' => 'MANGO-002',
        'short_description' => 'Sweet and juicy mango.',
        'price' => 240.00,
        'compare_at_price' => 299.00,
        'stock' => 20,
        'type' => 'simple',
        'is_active' => true,
    ]);

    $response = $this->get(route('shop'));

    $response->assertOk();
    $response->assertSee('Category');
    $response->assertSee('Price Range');
    $response->assertSee('Product Variants');
    $response->assertDontSee('Brands');
    $response->assertDontSee('Apply Filter');
    $response->assertDontSee('Reset');
});

it('applies a min-price filter without forcing an impossible max range', function () {
    $category = Category::create([
        'name' => 'Fresh Fruits',
        'slug' => 'fresh-fruits',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    Product::create([
        'category_id' => $category->id,
        'name' => 'Fresh Mango',
        'slug' => 'fresh-mango',
        'sku' => 'MANGO-004',
        'short_description' => 'Sweet and juicy mango.',
        'price' => 240.00,
        'compare_at_price' => 299.00,
        'stock' => 20,
        'type' => 'simple',
        'is_active' => true,
    ]);

    $response = $this->get(route('shop', ['min_price' => 200]));

    $response->assertOk();
    $response->assertSee('Fresh Mango');
});

it('stores a product in the database cart', function () {
    $category = Category::create([
        'name' => 'Fresh Fruits',
        'slug' => 'fresh-fruits',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Fresh Mango',
        'slug' => 'fresh-mango',
        'sku' => 'MANGO-003',
        'short_description' => 'Sweet and juicy mango.',
        'price' => 240.00,
        'compare_at_price' => 299.00,
        'stock' => 20,
        'type' => 'simple',
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/cart/items', [
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('carts', ['session_id' => session()->getId()]);
    $this->assertDatabaseHas('cart_items', [
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
});

it('creates an order from the current cart', function () {
    $category = Category::create([
        'name' => 'Fresh Fruits',
        'slug' => 'fresh-fruits',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Fresh Mango',
        'slug' => 'fresh-mango',
        'sku' => 'MANGO-005',
        'short_description' => 'Sweet and juicy mango.',
        'price' => 240.00,
        'compare_at_price' => 299.00,
        'stock' => 20,
        'type' => 'simple',
        'is_active' => true,
    ]);

    $cart = Cart::query()->create([
        'session_id' => session()->getId(),
    ]);
    session()->put('cart_id', $cart->id);

    $cart->items()->create([
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => $product->price,
        'quantity' => 2,
    ]);

    $response = $this->postJson(route('checkout.store'), [
        'cart_id' => $cart->id,
        'customer_name' => 'John Doe',
        'phone' => '01712345678',
        'address' => 'House 12, Road 6, Dhanmondi',
        'city' => 'Dhaka',
        'area' => 'Dhanmondi',
        'payment_method' => 'cod',
        'notes' => 'Please call before delivery.',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('orders', [
        'user_id' => null,
        'customer_name' => 'John Doe',
        'phone' => '01712345678',
        'payment_method' => 'cod',
    ]);
    $this->assertDatabaseHas('order_items', [
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
});

it('links an account order and updates the customer profile', function () {
    $customer = User::factory()->create([
        'name' => 'Old Customer Name',
        'phone' => null,
    ]);
    expect($customer->account_type)->toBe('customer');
    $category = Category::create([
        'name' => 'Fresh Fruits',
        'slug' => 'fresh-fruits',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Fresh Mango',
        'slug' => 'fresh-mango',
        'sku' => 'MANGO-CUSTOMER-001',
        'short_description' => 'Sweet and juicy mango.',
        'price' => 240.00,
        'compare_at_price' => 299.00,
        'stock' => 20,
        'type' => 'simple',
        'is_active' => true,
    ]);
    $cart = Cart::query()->create([
        'session_id' => session()->getId(),
        'user_id' => $customer->id,
    ]);
    session()->put('cart_id', $cart->id);
    $cart->items()->create([
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => $product->price,
        'quantity' => 1,
    ]);

    $this->actingAs($customer)
        ->postJson(route('checkout.store'), [
            'cart_id' => $cart->id,
            'customer_name' => 'Updated Customer Name',
            'phone' => '01712345678',
            'address' => 'House 12, Road 6, Dhanmondi',
            'city' => 'Dhaka',
            'payment_method' => 'cod',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('users', [
        'id' => $customer->id,
        'name' => 'Updated Customer Name',
        'phone' => '01712345678',
    ]);
    $this->assertDatabaseHas('orders', [
        'user_id' => $customer->id,
        'customer_name' => 'Updated Customer Name',
    ]);
});

it('shows the system owner dashboard with permissions and maintenance overview', function () {
    $this->actingAs(completeManagementTwoFactorForTest(createSystemOwnerUser()));

    $response = $this->get(route('admin'));

    $response->assertOk();
    $response->assertSee('System Owner Overview');
    $response->assertSee('Current User Permissions');
    $response->assertSee('Maintainable Actions');
    $response->assertSee('System Owner');
    $response->assertSee('Manage Products');
});

it('loads the checkout page', function () {
    $response = $this->get(route('checkout'));

    $response->assertOk();
    $response->assertSee('Cash on Delivery');
});

it('displays product detail carousel with all product images and counter', function () {
    $category = Category::create([
        'name' => 'Organic Honey',
        'slug' => 'organic-honey',
        'is_active' => true,
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Sundarban Raw Honey',
        'slug' => 'sundarban-raw-honey',
        'sku' => 'HNY-001',
        'short_description' => '100% natural raw forest honey.',
        'price' => 650.00,
        'stock' => 15,
        'type' => 'simple',
        'is_active' => true,
    ]);

    ProductImage::create([
        'product_id' => $product->id,
        'image_path' => 'products/honey-front.jpg',
        'alt_text' => 'Front view',
        'is_primary' => true,
        'sort_order' => 0,
    ]);

    ProductImage::create([
        'product_id' => $product->id,
        'image_path' => 'products/honey-nutrition.jpg',
        'alt_text' => 'Nutrition facts',
        'is_primary' => false,
        'sort_order' => 1,
    ]);

    ProductImage::create([
        'product_id' => $product->id,
        'image_path' => 'products/honey-back.jpg',
        'alt_text' => 'Back view',
        'is_primary' => false,
        'sort_order' => 2,
    ]);

    $response = $this->get(route('products.show', ['product' => $product->slug]));

    $response->assertOk();
    $response->assertSee('id="product-carousel-track"', false);
    $response->assertSee('products/honey-front.jpg');
    $response->assertSee('products/honey-nutrition.jpg');
    $response->assertSee('products/honey-back.jpg');
    $response->assertSee('1 / 3');
    $response->assertSee('★ Primary');
    $response->assertSee('id="carousel-prev-btn"', false);
    $response->assertSee('id="carousel-next-btn"', false);
});

it('displays designated primary image on shop and home product cards', function () {
    $category = Category::create([
        'name' => 'Beverages',
        'slug' => 'beverages',
        'is_active' => true,
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Green Tea Pack',
        'slug' => 'green-tea-pack',
        'sku' => 'TEA-001',
        'short_description' => 'Refreshing organic green tea.',
        'price' => 320.00,
        'stock' => 40,
        'type' => 'simple',
        'is_active' => true,
    ]);

    // Secondary image
    ProductImage::create([
        'product_id' => $product->id,
        'image_path' => 'products/tea-box-side.jpg',
        'alt_text' => 'Tea side',
        'is_primary' => false,
        'sort_order' => 1,
    ]);

    // Primary image
    ProductImage::create([
        'product_id' => $product->id,
        'image_path' => 'products/tea-box-front-primary.jpg',
        'alt_text' => 'Tea front primary',
        'is_primary' => true,
        'sort_order' => 0,
    ]);

    $shopResponse = $this->get(route('shop'));
    $shopResponse->assertOk();
    $shopResponse->assertSee('products/tea-box-front-primary.jpg');

    $homeResponse = $this->get(route('home'));
    $homeResponse->assertOk();
    $homeResponse->assertSee('products/tea-box-front-primary.jpg');
});

it('includes product image in cart payload and displays it on checkout', function () {
    $category = Category::create([
        'name' => 'Bakery',
        'slug' => 'bakery',
        'is_active' => true,
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Garlic Bread',
        'slug' => 'garlic-bread',
        'sku' => 'BRD-001',
        'price' => 110.00,
        'stock' => 20,
        'type' => 'simple',
        'is_active' => true,
    ]);

    ProductImage::create([
        'product_id' => $product->id,
        'image_path' => 'products/garlic-bread-thumb.jpg',
        'is_primary' => true,
        'sort_order' => 0,
    ]);

    $cartResponse = $this->postJson('/api/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $cartResponse->assertOk();
    $cartResponse->assertJsonPath('items.0.image_url', '/storage/products/garlic-bread-thumb.jpg');

    $checkoutResponse = $this->get(route('checkout'));
    $checkoutResponse->assertOk();
    $checkoutResponse->assertSee('products/garlic-bread-thumb.jpg');
});
