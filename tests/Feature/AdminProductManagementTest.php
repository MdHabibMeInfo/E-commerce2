<?php

use App\Models\Category;
use App\Models\Product;

beforeEach(function () {
    $this->actingAs(completeManagementTwoFactorForTest(createSystemOwnerUser()));
});

it('loads the admin product management index', function () {
    $response = $this->get(route('admin.products.index'));

    $response->assertOk();
    $response->assertSee('Product Management');
    $response->assertSee('Create Product');
});

it('creates, updates, deletes, and restores a product from the admin module', function () {
    $category = Category::create([
        'name' => 'Vegetables',
        'slug' => 'vegetables',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $response = $this->post(route('admin.products.store'), [
        'category_id' => $category->id,
        'name' => 'Fresh Tomato',
        'slug' => 'fresh-tomato',
        'sku' => 'TOM-100',
        'short_description' => 'Ripe and juicy tomato.',
        'description' => 'Fresh red tomato for daily cooking.',
        'type' => 'simple',
        'price' => 120,
        'compare_at_price' => 150,
        'stock' => 18,
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.products.index'));
    $this->assertDatabaseHas('products', ['slug' => 'fresh-tomato']);

    $product = Product::query()->where('slug', 'fresh-tomato')->firstOrFail();

    $updateResponse = $this->put(route('admin.products.update', $product), [
        'category_id' => $category->id,
        'name' => 'Fresh Tomato Premium',
        'slug' => 'fresh-tomato-premium',
        'sku' => 'TOM-101',
        'short_description' => 'Premium ripe tomato.',
        'description' => 'Premium grade fresh tomato.',
        'type' => 'simple',
        'price' => 140,
        'compare_at_price' => 180,
        'stock' => 20,
        'is_active' => true,
    ]);

    $updateResponse->assertRedirect(route('admin.products.index'));
    $this->assertDatabaseHas('products', ['slug' => 'fresh-tomato-premium']);

    $deleteResponse = $this->delete(route('admin.products.destroy', $product));
    $deleteResponse->assertRedirect(route('admin.products.index'));
    $this->assertSoftDeleted('products', ['id' => $product->id]);

    $restoreResponse = $this->post(route('admin.products.restore', $product));
    $restoreResponse->assertRedirect(route('admin.products.index'));
    $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
});
