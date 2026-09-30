<?php

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Services\ProductVariantService;

it('loads the home page', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

it('lists out-of-stock products last and disables their cart button', function () {
    Product::create([
        'name' => 'Available Product First',
        'slug' => 'available-product-first',
        'price' => 240,
        'type' => 'simple',
        'is_active' => true,
    ]);
    $outOfStockProduct = Product::create([
        'name' => 'Unavailable Product Last',
        'slug' => 'unavailable-product-last',
        'price' => 10,
        'type' => 'simple',
        'is_active' => true,
    ]);
    $outOfStockProduct->forceFill(['is_out_of_stock' => true])->save();

    $this->get(route('shop', ['sort' => 'price_asc']))
        ->assertOk()
        ->assertSeeInOrder(['Available Product First', 'Unavailable Product Last'])
        ->assertSee('Out of stock')
        ->assertSee('disabled', false);

    $this->get(route('products.show', 'unavailable-product-last'))
        ->assertOk()
        ->assertSee('Out of stock')
        ->assertSee('disabled', false)
        ->assertDontSee('data-buy-now', false);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Unavailable Product Last')
        ->assertSee('Out of stock');
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

it('requires a variant when adding a variable product to the cart', function () {
    $product = Product::create([
        'name' => 'Variable Shirt',
        'slug' => 'variable-shirt-cart',
        'type' => 'variable',
        'is_active' => true,
    ]);

    $this->postJson('/api/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('product_variant_id');

    $this->assertDatabaseMissing('cart_items', [
        'product_id' => $product->id,
    ]);
});

it('keeps variant cart lines distinct and enforces available stock', function () {
    $product = Product::create([
        'name' => 'Variable Cart Shirt',
        'slug' => 'variable-cart-shirt',
        'type' => 'variable',
        'is_active' => true,
    ]);
    $color = ProductAttribute::create([
        'name' => 'Color',
        'slug' => 'cart-color',
        'is_active' => true,
    ]);
    $product->productAttributes()->attach($color);
    $red = $color->values()->create([
        'value' => 'Red',
        'slug' => 'cart-red',
        'is_active' => true,
    ]);
    $blue = $color->values()->create([
        'value' => 'Blue',
        'slug' => 'cart-blue',
        'is_active' => true,
    ]);
    $variantService = app(ProductVariantService::class);
    $redVariant = $variantService->createVariant($product, [
        'name' => 'Red',
        'sku' => 'CART-SHIRT-RED',
        'attribute_value_ids' => [$red->id],
        'price' => 25,
        'stock' => 2,
    ]);
    $blueVariant = $variantService->createVariant($product, [
        'name' => 'Blue',
        'sku' => 'CART-SHIRT-BLUE',
        'attribute_value_ids' => [$blue->id],
        'price' => 27,
        'stock' => 4,
    ]);

    $this->postJson('/api/cart/items', [
        'product_id' => $product->id,
        'product_variant_id' => $redVariant->id,
        'quantity' => 1,
        'price' => 0,
    ])->assertOk()->assertJsonPath('items.0.price', 25);

    $redAgainResponse = $this->postJson('/api/cart/items', [
        'product_id' => $product->id,
        'product_variant_id' => $redVariant->id,
        'quantity' => 1,
    ]);
    $this->assertDatabaseCount('carts', 1);
    $redAgainResponse->assertOk()->assertJsonFragment([
        'product_variant_id' => $redVariant->id,
        'quantity' => 2,
    ]);

    $blueResponse = $this->postJson('/api/cart/items', [
        'product_id' => $product->id,
        'product_variant_id' => $blueVariant->id,
        'quantity' => 1,
    ]);
    $blueResponse->assertOk()->assertJsonFragment([
        'product_variant_id' => $blueVariant->id,
        'price' => 27,
    ]);

    $this->postJson('/api/cart/items', [
        'product_id' => $product->id,
        'product_variant_id' => $redVariant->id,
        'quantity' => 1,
    ])->assertUnprocessable()->assertJsonValidationErrors('quantity');

    $this->assertDatabaseCount('cart_items', 2);
    $this->assertDatabaseHas('cart_items', [
        'product_variant_id' => $redVariant->id,
        'quantity' => 2,
        'price' => 25,
    ]);
});

it('checks out a selected variant and reserves its stock', function () {
    $product = Product::create([
        'name' => 'Checkout Variant Shirt',
        'slug' => 'checkout-variant-shirt',
        'type' => 'variable',
        'is_active' => true,
    ]);
    $size = ProductAttribute::create([
        'name' => 'Size',
        'slug' => 'checkout-size',
        'is_active' => true,
    ]);
    $product->productAttributes()->attach($size);
    $large = $size->values()->create([
        'value' => 'Large',
        'slug' => 'checkout-large',
        'is_active' => true,
    ]);
    $variant = app(ProductVariantService::class)->createVariant($product, [
        'sku' => 'CHECKOUT-SHIRT-LARGE',
        'attribute_value_ids' => [$large->id],
        'price' => 31,
        'stock' => 3,
    ]);
    $cart = Cart::query()->create(['session_id' => session()->getId()]);
    session()->put('cart_id', $cart->id);
    $cart->items()->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'variant_name' => $variant->displayName(),
        'line_key' => 'variant:'.$variant->id,
        'name' => $product->name,
        'price' => 1,
        'quantity' => 2,
    ]);

    $this->post(route('checkout.store'), [
        'cart_id' => $cart->id,
        'customer_name' => 'John Doe',
        'phone' => '01712345678',
        'address' => 'House 12, Road 6, Dhanmondi',
        'city' => 'Dhaka',
        'payment_method' => 'cod',
    ])->assertRedirect();

    $this->assertDatabaseHas('order_items', [
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'variant_name' => 'Size: Large',
        'variant_sku' => 'CHECKOUT-SHIRT-LARGE',
        'price' => 31,
        'quantity' => 2,
    ]);
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'stock' => 1,
    ]);
    $this->assertDatabaseCount('cart_items', 0);
});

it('rejects out-of-stock products from the cart API', function () {
    $product = Product::create([
        'name' => 'Unavailable Mango',
        'slug' => 'unavailable-mango',
        'price' => 240,
        'stock' => 12,
        'type' => 'simple',
        'is_active' => true,
    ]);
    $product->forceFill(['is_out_of_stock' => true])->save();

    $this->postJson('/api/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('product_id');

    $this->assertDatabaseMissing('cart_items', ['product_id' => $product->id]);
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
        'customer_name' => 'John Doe',
        'phone' => '01712345678',
        'payment_method' => 'cod',
    ]);
    $this->assertDatabaseHas('order_items', [
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
});

it('rejects checkout when a product in the cart is out of stock', function () {
    $product = Product::create([
        'name' => 'Unavailable Bread',
        'slug' => 'unavailable-bread',
        'price' => 110,
        'stock' => 20,
        'type' => 'simple',
        'is_active' => true,
    ]);
    $product->forceFill(['is_out_of_stock' => true])->save();

    $cart = Cart::query()->create(['session_id' => session()->getId()]);
    $cart->items()->create([
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => $product->price,
        'quantity' => 1,
    ]);

    $this->post(route('checkout.store'), [
        'cart_id' => $cart->id,
        'customer_name' => 'John Doe',
        'phone' => '01712345678',
        'address' => 'House 12, Road 6, Dhanmondi',
        'city' => 'Dhaka',
        'payment_method' => 'cod',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors('cart');

    $this->assertDatabaseCount('orders', 0);
    $this->assertDatabaseHas('cart_items', ['product_id' => $product->id]);
});

it('shows the system owner dashboard with permissions and maintenance overview', function () {
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

it('shows active variant options and routes variable products to option selection', function () {
    $product = Product::create([
        'name' => 'Variant Detail Shirt',
        'slug' => 'variant-detail-shirt',
        'type' => 'variable',
        'is_active' => true,
    ]);
    $color = ProductAttribute::create([
        'name' => 'Color',
        'slug' => 'detail-color',
        'is_active' => true,
    ]);
    $product->productAttributes()->attach($color);
    $red = $color->values()->create([
        'value' => 'Red',
        'slug' => 'detail-red',
        'is_active' => true,
    ]);
    app(ProductVariantService::class)->createVariant($product, [
        'sku' => 'DETAIL-SHIRT-RED',
        'attribute_value_ids' => [$red->id],
        'price' => 29,
        'stock' => 3,
    ]);

    $this->get(route('products.show', $product->slug))
        ->assertOk()
        ->assertSee('data-product-variant-select', false)
        ->assertSee('Color: Red')
        ->assertSee('Select an in-stock option to continue.');

    $this->get(route('shop'))
        ->assertOk()
        ->assertSee('Choose options')
        ->assertSee('data-select-variant', false);
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
