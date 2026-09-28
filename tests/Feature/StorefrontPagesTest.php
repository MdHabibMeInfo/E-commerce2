<?php

use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;

it('loads the home page', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

it('shows a hero carousel with product slides and navigation controls', function () {
    $category = Category::create([
        'name' => 'Fresh Fruits',
        'slug' => 'fresh-fruits',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    foreach (range(1, 3) as $productNumber) {
        Product::create([
            'category_id' => $category->id,
            'name' => 'Carousel Product ' . $productNumber,
            'slug' => 'carousel-product-' . $productNumber,
            'sku' => 'CAROUSEL-' . $productNumber,
            'short_description' => 'Fresh produce for every day.',
            'price' => 100 + $productNumber,
            'stock' => 20,
            'type' => 'simple',
            'is_active' => true,
        ]);
    }

    $response = $this->get(route('home'));

    $response->assertSee('data-hero-carousel', false);
    $response->assertSee('data-hero-prev', false);
    $response->assertSee('data-hero-next', false);
    $response->assertSee('data-hero-dot="0"', false);
    $response->assertSeeTextInOrder(['Carousel Product 1', 'Carousel Product 2', 'Carousel Product 3']);
});

it('links home and shop product cards to an image-backed product detail page', function () {
    $category = Category::create([
        'name' => 'Fresh Fruits',
        'slug' => 'fresh-fruits',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Detail Page Mango',
        'slug' => 'detail-page-mango',
        'sku' => 'DETAIL-MANGO-001',
        'price' => 240,
        'stock' => 20,
        'type' => 'simple',
        'is_active' => true,
    ]);

    ProductImage::create([
        'product_id' => $product->id,
        'image_path' => 'products/detail-page-mango.jpg',
        'alt_text' => 'Fresh mangoes',
        'is_primary' => true,
        'sort_order' => 0,
    ]);

    $detailUrl = route('products.show', ['product' => $product->slug]);

    foreach ([route('home'), route('shop')] as $listingUrl) {
        $response = $this->get($listingUrl);

        $response->assertSee('href="' . $detailUrl . '"', false);
    }

    $detailResponse = $this->get($detailUrl);

    $detailResponse->assertSee('Detail Page Mango');
    $detailResponse->assertSee('products/detail-page-mango.jpg');
    $detailResponse->assertSee('id="product-carousel-track"', false);
});

it('submits homepage searches to the shop and filters matching products', function () {
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
        'sku' => 'SEARCH-MANGO-001',
        'price' => 240,
        'stock' => 20,
        'type' => 'simple',
        'is_active' => true,
    ]);
    Product::create([
        'category_id' => $category->id,
        'name' => 'Brown Rice',
        'slug' => 'brown-rice',
        'sku' => 'SEARCH-RICE-001',
        'price' => 180,
        'stock' => 20,
        'type' => 'simple',
        'is_active' => true,
    ]);

    $homeResponse = $this->get(route('home'));
    $searchResponse = $this->get(route('shop', ['q' => 'Fresh Mango']));

    expect(substr_count($homeResponse->getContent(), 'action="' . route('shop') . '" method="GET"'))->toBe(2);
    $searchResponse->assertSee('Fresh Mango');
    $searchResponse->assertDontSee('Brown Rice');
});

it('shows featured product pagination without arrow controls on the home page', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('data-product-pagination');
    $response->assertDontSee('data-product-prev');
    $response->assertDontSee('data-product-next');
});

it('shows ten products in each homepage category row', function () {
    $category = Category::create([
        'name' => 'Fresh Fruits',
        'slug' => 'fresh-fruits',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    foreach (range(1, 10) as $productNumber) {
        Product::create([
            'category_id' => $category->id,
            'name' => 'Fruit ' . $productNumber,
            'slug' => 'fruit-' . $productNumber,
            'sku' => 'FRUIT-' . $productNumber,
            'price' => 100 + $productNumber,
            'stock' => 20,
            'type' => 'simple',
            'is_active' => true,
        ]);
    }

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('Fruit 1');
    $response->assertSee('Fruit 10');
    $response->assertSee('data-category-product-pagination="fresh-fruits"', false);
});

it('shows top selling products ranked by ordered quantity', function () {
    $category = Category::create([
        'name' => 'Grocery Staples',
        'slug' => 'grocery-staples',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $topSeller = Product::create([
        'category_id' => $category->id,
        'name' => 'Top Seller',
        'slug' => 'top-seller',
        'sku' => 'TOP-SELLER-001',
        'price' => 250,
        'stock' => 20,
        'type' => 'simple',
        'is_active' => true,
    ]);
    $runnerUp = Product::create([
        'category_id' => $category->id,
        'name' => 'Runner Up',
        'slug' => 'runner-up',
        'sku' => 'RUNNER-UP-001',
        'price' => 200,
        'stock' => 20,
        'type' => 'simple',
        'is_active' => true,
    ]);

    $order = Order::create([
        'customer_name' => 'Storefront Buyer',
        'phone' => '0123456789',
        'address' => '1 Market Street',
        'city' => 'Dhaka',
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $topSeller->id,
        'name' => $topSeller->name,
        'price' => $topSeller->price,
        'quantity' => 8,
    ]);
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $runnerUp->id,
        'name' => $runnerUp->name,
        'price' => $runnerUp->price,
        'quantity' => 3,
    ]);

    $response = $this->get(route('home'));

    $response->assertSeeTextInOrder(['Top Selling Products', 'Top Seller', 'Runner Up']);
    $response->assertSee('Best Selling');
    $response->assertSee('data-buy-now', false);
});

it('shows only product names and current prices on home and shop cards', function () {
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
        $response->assertDontSee('Sweet and juicy mango.');
        $response->assertDontSee('299.00');
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

    foreach (
        [
            ['name' => 'Old Product', 'sku' => 'SORT-001', 'price' => 200.00],
            ['name' => 'Middle Product', 'sku' => 'SORT-002', 'price' => 500.00],
            ['name' => 'New Product', 'sku' => 'SORT-003', 'price' => 100.00],
        ] as $productData
    ) {
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
