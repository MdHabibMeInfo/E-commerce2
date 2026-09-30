<?php

use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->actingAs(completeManagementTwoFactorForTest(createSystemOwnerUser()));
});

it('loads the admin product management index', function () {
    $response = $this->get(route('admin.products.index'));

    $response->assertOk();
    $response->assertSee('Product Management');
    $response->assertSee('Create Product');
    $response->assertSee('data-admin-search', false);
    $response->assertSee('name="status"', false);
    $response->assertSee('name="category_id"', false);
    $response->assertSee('name="type"', false);
    $response->assertSee('data-admin-filter-input', false);
    $response->assertSee('data-filter-options="status"', false);
    $response->assertSee('data-filter-options="type"', false);
});

it('allows the owner to toggle product availability without entering a quantity', function () {
    $product = Product::create([
        'name' => 'Availability Toggle Product',
        'slug' => 'availability-toggle-product',
        'type' => 'simple',
        'price' => 10,
        'is_active' => true,
    ]);

    $this->get(route('admin.products.create'))
        ->assertDontSee('name="stock"', false);
    $this->get(route('admin.products.edit', $product))
        ->assertDontSee('name="stock"', false);

    $this->post(route('admin.products.store'), [
        'name' => 'Product Without Quantity',
        'slug' => 'product-without-quantity',
        'type' => 'simple',
        'price' => 15,
        'is_active' => true,
    ])->assertRedirect(route('admin.products.index'));

    $this->assertDatabaseHas('products', [
        'slug' => 'product-without-quantity',
        'stock' => null,
        'is_out_of_stock' => false,
    ]);

    $this->patch(route('admin.products.availability', $product), ['is_out_of_stock' => true])
        ->assertRedirect(route('admin.products.index'));

    expect($product->fresh()->is_out_of_stock)->toBeTrue();

    $this->get(route('admin.products.index'))
        ->assertSee('Out of stock')
        ->assertSee('Mark available');

    $this->patch(route('admin.products.availability', $product), ['is_out_of_stock' => false])
        ->assertRedirect(route('admin.products.index'));

    expect($product->fresh()->is_out_of_stock)->toBeFalse();
});

it('shows attribute assignment and variant management for variable products', function () {
    $product = Product::create([
        'name' => 'Variable Dashboard Product',
        'slug' => 'variable-dashboard-product',
        'type' => 'variable',
        'is_active' => true,
    ]);

    $this->get(route('admin.products.show', $product))
        ->assertOk()
        ->assertSee('Product Attributes')
        ->assertSee('Product Variants')
        ->assertSee('Assign Attributes');
});

it('manages variant assignment and lifecycle from the product detail page', function () {
    $product = Product::create([
        'name' => 'Variant Lifecycle Product',
        'slug' => 'variant-lifecycle-product',
        'type' => 'variable',
        'is_active' => true,
    ]);
    $attribute = ProductAttribute::create([
        'name' => 'Color',
        'slug' => 'variant-lifecycle-color',
        'is_active' => true,
    ]);
    $value = AttributeValue::create([
        'product_attribute_id' => $attribute->id,
        'value' => 'Blue',
        'slug' => 'blue',
        'is_active' => true,
    ]);

    $this->put(route('admin.products.attributes.update', $product), [
        'product_attribute_ids' => [$attribute->id],
    ])->assertRedirect(route('admin.products.show', $product));

    $this->post(route('admin.products.variants.store', $product), [
        'name' => 'Blue / Small',
        'sku' => 'VARIANT-LIFECYCLE-BLUE-S',
        'attribute_value_ids' => [$value->id],
        'price' => 25,
        'stock' => 5,
        'is_active' => true,
    ])->assertRedirect(route('admin.products.show', $product));

    $variant = $product->variants()->firstOrFail();

    $this->from(route('admin.products.show', $product))
        ->post(route('admin.products.variants.store', $product), [
            'name' => 'Duplicate Blue',
            'sku' => 'VARIANT-LIFECYCLE-BLUE-DUPLICATE',
            'attribute_value_ids' => [$value->id],
            'price' => 26,
            'stock' => 3,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.products.show', $product))
        ->assertSessionHasErrors('attribute_value_ids');

    $this->put(route('admin.products.variants.update', [$product, $variant]), [
        'name' => 'Blue / Medium',
        'sku' => 'VARIANT-LIFECYCLE-BLUE-M',
        'attribute_value_ids' => [$value->id],
        'price' => 27,
        'stock' => 4,
        'is_active' => true,
    ])->assertRedirect(route('admin.products.show', $product));

    expect($variant->fresh()->sku)->toBe('VARIANT-LIFECYCLE-BLUE-M');

    $this->delete(route('admin.products.variants.destroy', [$product, $variant]))
        ->assertRedirect(route('admin.products.show', $product));
    $this->assertSoftDeleted('product_variants', ['id' => $variant->id]);

    $this->post(route('admin.products.variants.restore', [$product, $variant]))
        ->assertRedirect(route('admin.products.show', $product));
    $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'deleted_at' => null]);
});

it('paginates products so all catalog records remain accessible', function () {
    foreach (range(1, 26) as $recordNumber) {
        $product = Product::create([
            'name' => sprintf('Pagination Product %03d', $recordNumber),
            'slug' => sprintf('pagination-product-%03d', $recordNumber),
            'type' => 'simple',
            'price' => 10,
            'stock' => 1,
            'is_active' => true,
        ]);

        $product->forceFill(['created_at' => now()->addSeconds($recordNumber)])->save();
    }

    $this->get(route('admin.products.index'))
        ->assertOk()
        ->assertSee('Pagination Product 026')
        ->assertDontSee('Pagination Product 001');

    $this->get(route('admin.products.index', ['page' => 2]))
        ->assertOk()
        ->assertSee('Pagination Product 001')
        ->assertDontSee('Pagination Product 026');
});

it('updates product active status in both directions', function () {
    $product = Product::create([
        'name' => 'Status Product',
        'slug' => 'status-product',
        'type' => 'simple',
        'is_active' => true,
    ]);

    $this->get(route('admin.products.edit', $product))
        ->assertOk()
        ->assertSee('type="hidden" name="is_active" value="0"', false);

    $this->put(route('admin.products.update', $product), [
        'name' => $product->name,
        'type' => $product->type,
        'price' => 10,
        'stock' => 1,
        'is_active' => false,
    ])->assertRedirect(route('admin.products.index'));

    expect($product->fresh()->is_active)->toBeFalse();
    $this->get(route('admin.products.index'))
        ->assertOk()
        ->assertSee('Inactive');

    $this->put(route('admin.products.update', $product), [
        'name' => $product->name,
        'type' => $product->type,
        'price' => 10,
        'stock' => 1,
        'is_active' => true,
    ])->assertRedirect(route('admin.products.index'));

    expect($product->fresh()->is_active)->toBeTrue();
});

it('finds a product beyond 5000 records and suggests it by slug', function () {
    $category = Category::create([
        'name' => 'Filter Target Category',
        'slug' => 'filter-target-category',
        'is_active' => true,
    ]);

    foreach (array_chunk(range(1, 5001), 100) as $recordNumbers) {
        $products = [];

        foreach ($recordNumbers as $recordNumber) {
            $isSearchTarget = $recordNumber === 5001;
            $products[] = [
                'category_id' => $isSearchTarget ? $category->id : null,
                'name' => $isSearchTarget ? 'Rare Search Product' : sprintf('Bulk Product %04d', $recordNumber),
                'slug' => $isSearchTarget ? 'rare-deep-product-target' : sprintf('bulk-product-%04d', $recordNumber),
                'type' => $isSearchTarget ? 'variable' : 'simple',
                'is_active' => ! $isSearchTarget,
                'created_at' => now()->subSeconds($recordNumber),
                'updated_at' => now()->subSeconds($recordNumber),
            ];
        }

        DB::table('products')->insert($products);
    }

    $this->get(route('admin.products.index', [
        'search' => 'rare-deep-product-target',
        'status' => 'inactive',
        'category_id' => $category->id,
        'type' => 'variable',
    ]))
        ->assertOk()
        ->assertSee('Rare Search Product');

    $this->getJson(route('admin.products.suggestions', ['search' => 'rare-deep-product-target']))
        ->assertOk()
        ->assertJsonFragment(['label' => 'Rare Search Product']);

    $this->getJson(route('admin.products.suggestions'))
        ->assertOk()
        ->assertJsonCount(8);

    $this->getJson(route('admin.categories.suggestions', ['search' => 'Filter Target Category']))
        ->assertOk()
        ->assertJsonFragment(['id' => $category->id, 'label' => 'Filter Target Category']);
});

it('suggests only soft-deleted products on the deleted page', function () {
    $deletedProduct = Product::create([
        'name' => 'Soft Deleted Product',
        'slug' => 'soft-deleted-product',
        'type' => 'simple',
        'price' => 10,
        'stock' => 5,
        'is_active' => true,
    ]);
    $deletedProduct->delete();

    $activeProduct = Product::create([
        'name' => 'Active Product Match',
        'slug' => 'active-product-match',
        'type' => 'simple',
        'price' => 10,
        'stock' => 5,
        'is_active' => true,
    ]);

    foreach (range(1, 8) as $recordNumber) {
        $suggestionProduct = Product::create([
            'name' => sprintf('ZZ Deleted Product %02d', $recordNumber),
            'slug' => sprintf('zz-deleted-product-%02d', $recordNumber),
            'type' => 'simple',
            'price' => 10,
            'stock' => 5,
            'is_active' => true,
        ]);
        $suggestionProduct->delete();
    }

    $this->get(route('admin.products.trashed'))
        ->assertSee('data-suggestions-url="'.route('admin.products.suggestions', ['trashed' => true]).'"', false);

    $this->getJson(route('admin.products.suggestions', ['search' => 'product', 'trashed' => true]))
        ->assertOk()
        ->assertJsonFragment(['label' => 'Soft Deleted Product'])
        ->assertJsonMissing(['label' => 'Active Product Match']);

    $this->getJson(route('admin.products.suggestions', ['trashed' => true]))
        ->assertOk()
        ->assertJsonCount(8)
        ->assertJsonMissing(['label' => 'Active Product Match']);
});

it('suggests deleted products when searching by a slug longer than 100 characters', function () {
    $longSlug = str_repeat('long-deleted-product-segment-', 5).'rare-target';
    $product = Product::create([
        'name' => 'Long Slug Deleted Product',
        'slug' => $longSlug,
        'type' => 'simple',
        'price' => 10,
        'stock' => 5,
        'is_active' => true,
    ]);
    $product->delete();

    $this->getJson(route('admin.products.suggestions', [
        'search' => $longSlug,
        'trashed' => true,
    ]))
        ->assertOk()
        ->assertJsonFragment(['label' => 'Long Slug Deleted Product']);

    $this->get(route('admin.products.trashed'))
        ->assertSee('maxlength="255"', false);
});

it('filters deleted products by categories that contain deleted products', function () {
    $activeCategory = Category::create([
        'name' => 'Active Deleted Product Category',
        'slug' => 'active-deleted-product-category',
        'is_active' => true,
    ]);
    $deletedCategory = Category::create([
        'name' => 'Deleted Category with Deleted Product',
        'slug' => 'deleted-category-with-deleted-product',
        'is_active' => true,
    ]);
    $deletedCategory->delete();
    $unusedCategory = Category::create([
        'name' => 'Category without Deleted Products',
        'slug' => 'category-without-deleted-products',
        'is_active' => true,
    ]);

    $activeCategoryProduct = Product::create([
        'category_id' => $activeCategory->id,
        'name' => 'Deleted Product in Active Category',
        'slug' => 'deleted-product-in-active-category',
        'type' => 'simple',
        'price' => 10,
        'stock' => 5,
        'is_active' => true,
    ]);
    $activeCategoryProduct->delete();

    $deletedCategoryProduct = Product::create([
        'category_id' => $deletedCategory->id,
        'name' => 'Deleted Product in Deleted Category',
        'slug' => 'deleted-product-in-deleted-category',
        'type' => 'simple',
        'price' => 10,
        'stock' => 5,
        'is_active' => true,
    ]);
    $deletedCategoryProduct->delete();

    $suggestionsUrl = route('admin.categories.suggestions', ['trashed_products' => true]);

    $this->get(route('admin.products.trashed', ['category_id' => $deletedCategory->id]))
        ->assertSee('data-filter-suggestions-url="'.$suggestionsUrl.'"', false)
        ->assertSee('value="Deleted Category with Deleted Product"', false)
        ->assertSee('Deleted Product in Deleted Category')
        ->assertDontSee('Deleted Product in Active Category');

    $this->getJson($suggestionsUrl)
        ->assertOk()
        ->assertJsonFragment(['id' => $activeCategory->id, 'label' => 'Active Deleted Product Category'])
        ->assertJsonFragment(['id' => $deletedCategory->id, 'label' => 'Deleted Category with Deleted Product'])
        ->assertJsonMissing(['id' => $unusedCategory->id]);
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
