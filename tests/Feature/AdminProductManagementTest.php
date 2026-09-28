<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

it('creates a product with multiple images and designates the selected primary image', function () {
    Storage::fake('public');

    $category = Category::create([
        'name' => 'Snacks',
        'slug' => 'snacks',
        'is_active' => true,
        'sort_order' => 5,
    ]);

    $file1 = UploadedFile::fake()->image('cookie-1.jpg', 600, 600);
    $file2 = UploadedFile::fake()->image('cookie-2.jpg', 600, 600);
    $file3 = UploadedFile::fake()->image('cookie-3.jpg', 600, 600);

    $response = $this->post(route('admin.products.store'), [
        'category_id' => $category->id,
        'name' => 'Chocolate Cookies',
        'slug' => 'chocolate-cookies',
        'sku' => 'SNK-100',
        'type' => 'simple',
        'price' => 150,
        'stock' => 50,
        'is_active' => true,
        'images' => [$file1, $file2, $file3],
        'primary_index' => 1,
    ]);

    $response->assertRedirect(route('admin.products.index'));

    $product = Product::query()->where('slug', 'chocolate-cookies')->firstOrFail();
    expect($product->images)->toHaveCount(3);

    $primaryImages = $product->images->where('is_primary', true);
    expect($primaryImages)->toHaveCount(1);

    $primaryImage = $primaryImages->first();
    expect($primaryImage->is_primary)->toBeTrue();
    expect($product->images->first()->id)->toBe($primaryImage->id);
    Storage::disk('public')->assertExists($primaryImage->image_path);
});

it('allows admin to set a different primary image and delete an image', function () {
    Storage::fake('public');

    $category = Category::create([
        'name' => 'Dairy',
        'slug' => 'dairy',
        'is_active' => true,
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Pure Milk',
        'slug' => 'pure-milk',
        'sku' => 'MLK-01',
        'type' => 'simple',
        'price' => 85,
        'stock' => 30,
        'is_active' => true,
    ]);

    $img1 = ProductImage::create([
        'product_id' => $product->id,
        'image_path' => 'products/milk-1.jpg',
        'is_primary' => true,
        'sort_order' => 0,
    ]);

    $img2 = ProductImage::create([
        'product_id' => $product->id,
        'image_path' => 'products/milk-2.jpg',
        'is_primary' => false,
        'sort_order' => 1,
    ]);

    // Set img2 as primary
    $response = $this->patch(route('admin.products.images.primary', [$product, $img2]));
    $response->assertRedirect();

    expect($img1->fresh()->is_primary)->toBeFalse()
        ->and($img2->fresh()->is_primary)->toBeTrue();

    // Delete img2 (which is primary), img1 should become primary automatically
    $deleteResponse = $this->delete(route('admin.products.images.destroy', [$product, $img2]));
    $deleteResponse->assertRedirect();

    expect(ProductImage::find($img2->id))->toBeNull()
        ->and($img1->fresh()->is_primary)->toBeTrue();
});

it('allows admin to update product and add additional images with primary_index', function () {
    Storage::fake('public');

    $category = Category::create([
        'name' => 'Spices',
        'slug' => 'spices',
        'is_active' => true,
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Chili Powder',
        'slug' => 'chili-powder',
        'sku' => 'CHL-001',
        'type' => 'simple',
        'price' => 60,
        'stock' => 100,
        'is_active' => true,
    ]);

    $file1 = UploadedFile::fake()->image('chili-1.jpg', 600, 600);
    $file2 = UploadedFile::fake()->image('chili-2.jpg', 600, 600);

    $response = $this->put(route('admin.products.update', $product), [
        'category_id' => $category->id,
        'name' => 'Premium Chili Powder',
        'slug' => 'premium-chili-powder',
        'sku' => 'CHL-001',
        'type' => 'simple',
        'price' => 70,
        'stock' => 90,
        'is_active' => true,
        'images' => [$file1, $file2],
        'primary_index' => 1,
    ]);

    $response->assertRedirect(route('admin.products.index'));

    $product->refresh();
    expect($product->name)->toBe('Premium Chili Powder')
        ->and($product->images)->toHaveCount(2);

    $primaryImage = $product->images->firstWhere('is_primary', true);
    expect($primaryImage)->not->toBeNull();
    Storage::disk('public')->assertExists($primaryImage->image_path);
});
