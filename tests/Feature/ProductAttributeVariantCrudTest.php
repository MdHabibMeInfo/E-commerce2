<?php

use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->actingAs(completeManagementTwoFactorForTest(createSystemOwnerUser()));
});

it('completes product attribute API CRUD and restore', function () {
    $this->postJson('/api/product-attributes', [
        'name' => 'Color',
        'slug' => 'color',
        'sort_order' => 1,
        'is_active' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('attribute.slug', 'color');

    $attribute = ProductAttribute::query()->where('slug', 'color')->firstOrFail();

    $this->getJson('/api/product-attributes?is_active=1')
        ->assertOk()
        ->assertJsonPath('pagination.total', 1);

    $this->putJson('/api/product-attributes/'.$attribute->id, [
        'name' => 'Color Family',
        'slug' => 'color-family',
        'sort_order' => 2,
        'is_active' => true,
    ])
        ->assertOk()
        ->assertJsonPath('attribute.slug', 'color-family');

    $this->deleteJson('/api/product-attributes/'.$attribute->id)
        ->assertOk();

    $this->getJson('/api/product-attributes?trashed=1')
        ->assertOk()
        ->assertJsonPath('pagination.total', 1);

    $this->patchJson('/api/product-attributes/'.$attribute->id.'/restore')
        ->assertOk()
        ->assertJsonPath('attribute.slug', 'color-family');

    $this->assertDatabaseHas('product_attributes', [
        'id' => $attribute->id,
        'deleted_at' => null,
    ]);
});

it('completes product variant API CRUD and restore within its product', function () {
    $product = Product::create([
        'name' => 'Variable Shirt',
        'slug' => 'variable-shirt',
        'type' => 'variable',
        'is_active' => true,
    ]);
    $otherProduct = Product::create([
        'name' => 'Other Variable Shirt',
        'slug' => 'other-variable-shirt',
        'type' => 'variable',
        'is_active' => true,
    ]);
    $attribute = ProductAttribute::create([
        'name' => 'Color',
        'slug' => 'color',
        'is_active' => true,
    ]);
    $product->productAttributes()->attach($attribute);
    $value = AttributeValue::create([
        'product_attribute_id' => $attribute->id,
        'value' => 'Red',
        'slug' => 'red',
        'is_active' => true,
    ]);

    $this->postJson('/api/products/'.$product->id.'/variants', [
        'name' => 'Red / M',
        'sku' => 'SHIRT-RED-M',
        'attribute_value_ids' => [$value->id],
        'price' => 25,
        'stock' => 8,
        'is_active' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('variant.sku', 'SHIRT-RED-M');

    $variant = $product->variants()->withTrashed()->firstOrFail();
    $variantUrl = '/api/products/'.$product->id.'/variants/'.$variant->id;

    $this->getJson('/api/products/'.$product->id.'/variants')
        ->assertOk()
        ->assertJsonPath('pagination.total', 1);

    $this->getJson($variantUrl)
        ->assertOk()
        ->assertJsonPath('variant.attributes.0.value', 'Red');

    $this->postJson('/api/products/'.$product->id.'/variants', [
        'name' => 'Duplicate Red Variant',
        'sku' => 'SHIRT-RED-DUPLICATE',
        'attribute_value_ids' => [$value->id],
        'price' => 25,
        'stock' => 8,
    ])->assertStatus(409);
    $this->assertDatabaseCount('product_variants', 1);

    $this->getJson('/api/products/'.$otherProduct->id.'/variants/'.$variant->id)
        ->assertNotFound();

    $this->putJson($variantUrl, [
        'name' => 'Red / Medium',
        'sku' => 'SHIRT-RED-MEDIUM',
        'attribute_value_ids' => [$value->id],
        'price' => 27,
        'stock' => 6,
        'is_active' => true,
    ])
        ->assertOk()
        ->assertJsonPath('variant.sku', 'SHIRT-RED-MEDIUM');

    $this->deleteJson($variantUrl)->assertOk();

    $this->getJson('/api/products/'.$product->id.'/variants?trashed=1')
        ->assertOk()
        ->assertJsonPath('pagination.total', 1);

    $this->patchJson($variantUrl.'/restore')
        ->assertOk()
        ->assertJsonPath('variant.sku', 'SHIRT-RED-MEDIUM');

    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'deleted_at' => null,
    ]);
});

it('prevents deleting a product attribute while it still owns values', function () {
    $attribute = ProductAttribute::create([
        'name' => 'Size',
        'slug' => 'size',
        'is_active' => true,
    ]);
    AttributeValue::create([
        'product_attribute_id' => $attribute->id,
        'value' => 'Large',
        'slug' => 'large',
        'is_active' => true,
    ]);

    $this->deleteJson('/api/product-attributes/'.$attribute->id)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('product_attribute');

    $this->assertDatabaseHas('product_attributes', [
        'id' => $attribute->id,
        'deleted_at' => null,
    ]);
});

it('rejects a variant combination missing an assigned product attribute', function () {
    $product = Product::create([
        'name' => 'Complete Combination Product',
        'slug' => 'complete-combination-product',
        'type' => 'variable',
        'is_active' => true,
    ]);
    $color = ProductAttribute::create(['name' => 'Color', 'slug' => 'combination-color', 'is_active' => true]);
    $size = ProductAttribute::create(['name' => 'Size', 'slug' => 'combination-size', 'is_active' => true]);
    $product->productAttributes()->attach([$color->id, $size->id]);
    $red = AttributeValue::create([
        'product_attribute_id' => $color->id,
        'value' => 'Red',
        'slug' => 'red',
        'is_active' => true,
    ]);

    $this->postJson('/api/products/'.$product->id.'/variants', [
        'sku' => 'INCOMPLETE-COMBINATION',
        'attribute_value_ids' => [$red->id],
        'price' => 25,
        'stock' => 8,
    ])
        ->assertUnprocessable()
        ->assertJsonFragment([
            'message' => 'Select exactly one active value for every attribute assigned to this product.',
        ]);

    $this->assertDatabaseMissing('product_variants', ['sku' => 'INCOMPLETE-COMBINATION']);
});

it('denies customers access to product attribute and variant management APIs', function () {
    $product = Product::create([
        'name' => 'Protected Variable Product',
        'slug' => 'protected-variable-product',
        'type' => 'variable',
        'is_active' => true,
    ]);
    $this->actingAs(User::factory()->create());

    $this->getJson('/api/product-attributes')->assertForbidden();
    $this->getJson('/api/products/'.$product->id.'/variants')->assertForbidden();
});

it('allows variant listing but denies creation without the create permission', function () {
    $product = Product::create([
        'name' => 'View Only Variable Product',
        'slug' => 'view-only-variable-product',
        'type' => 'variable',
        'is_active' => true,
    ]);
    $user = User::factory()->create();
    $attributeViewPermission = Permission::findOrCreate('attributes.view', 'web');
    $viewPermission = Permission::findOrCreate('variants.view', 'web');
    $user->givePermissionTo([$attributeViewPermission, $viewPermission]);
    $this->actingAs($user);

    $this->getJson('/api/product-attributes')->assertOk();
    $this->postJson('/api/product-attributes', [])->assertForbidden();
    $this->getJson('/api/products/'.$product->id.'/variants')->assertOk();

    $this->postJson('/api/products/'.$product->id.'/variants', [
        'sku' => 'VIEW-ONLY-VARIANT',
        'attribute_value_ids' => [1],
        'price' => 25,
        'stock' => 8,
    ])->assertForbidden();
});
