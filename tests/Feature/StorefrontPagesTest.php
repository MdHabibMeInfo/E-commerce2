<?php

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;

it('loads the home page', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
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
