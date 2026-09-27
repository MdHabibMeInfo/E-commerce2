<?php

use App\Models\Category;
use App\Models\Product;

it('auto-generates a slug when the submitted product slug is whitespace', function () {
    $category = Category::create([
        'name' => 'Bakery',
        'slug' => 'bakery',
        'is_active' => true,
        'sort_order' => 3,
    ]);

    $response = $this->post(route('admin.products.store'), [
        'category_id' => $category->id,
        'name' => 'Whole Wheat Bread',
        'slug' => '   ',
        'sku' => 'BREAD-001',
        'short_description' => 'Fresh and healthy.',
        'description' => 'Soft and healthy whole wheat bread.',
        'type' => 'simple',
        'price' => 90,
        'compare_at_price' => 120,
        'stock' => 25,
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.products.index'));
    $this->assertDatabaseHas('products', ['slug' => 'whole-wheat-bread']);
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

it('loads the product detail, create, edit, and deleted pages in the admin dashboard', function () {
    $category = Category::create([
        'name' => 'Fruits',
        'slug' => 'fruits',
        'is_active' => true,
        'sort_order' => 2,
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Mango',
        'slug' => 'mango',
        'sku' => 'FRU-200',
        'short_description' => 'Sweet and juicy mango.',
        'description' => 'Fresh mango harvested at peak quality.',
        'type' => 'simple',
        'price' => 180,
        'compare_at_price' => 220,
        'stock' => 12,
        'is_active' => true,
    ]);

    $this->get(route('admin.products.show', $product))
        ->assertOk()
        ->assertSee('Delete Product')
        ->assertSee('Edit Product');

    $this->get(route('admin.products.create'))
        ->assertOk()
        ->assertSee('Create Product');

    $this->get(route('admin.products.edit', $product))
        ->assertOk()
        ->assertSee('Update Product');

    $this->get(route('admin.products.trashed'))
        ->assertOk()
        ->assertSee('Deleted Products');
});
