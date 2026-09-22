<?php

namespace Tests\Feature\Services;

use App\Exceptions\DuplicateVariantCombinationException;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\AttributeValue;
use App\Services\ProductVariantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductVariantServiceTest extends TestCase
{
  use RefreshDatabase;

  public function test_variable_product_without_attributes_cannot_have_variants(): void
  {
    $product = Product::create([
      'name' => 'Variable Product',
      'slug' => 'variable-product',
      'type' => 'variable',
      'price' => null,
      'stock' => null,
      'is_active' => true,
    ]);

    $attribute = ProductAttribute::create([
      'name' => 'Color',
      'slug' => 'color',
      'sort_order' => 0,
      'is_active' => true,
    ]);

    $attributeValue = AttributeValue::create([
      'product_attribute_id' => $attribute->id,
      'value' => 'Red',
      'slug' => 'red',
      'sort_order' => 0,
      'is_active' => true,
    ]);

    $service = app(ProductVariantService::class);

    $this->expectException(ValidationException::class);

    $service->createVariant($product, [
      'name' => 'Red Variant',
      'sku' => 'RED-001',
      'attribute_value_ids' => [
        $attributeValue->id,
      ],
      'price' => 100,
      'stock' => 10,
      'is_active' => true,
    ]);
  }

  public function test_variable_product_with_valid_attributes_can_create_variant(): void
  {
    $product = Product::create([
      'name' => 'Variable Product',
      'slug' => 'variable-product',
      'type' => 'variable',
      'price' => null,
      'stock' => null,
      'is_active' => true,
    ]);

    $attribute = ProductAttribute::create([
      'name' => 'Color',
      'slug' => 'color',
      'sort_order' => 0,
      'is_active' => true,
    ]);

    $product->productAttributes()->attach($attribute->id);

    $attributeValue = AttributeValue::create([
      'product_attribute_id' => $attribute->id,
      'value' => 'Red',
      'slug' => 'red',
      'sort_order' => 0,
      'is_active' => true,
    ]);

    $service = app(ProductVariantService::class);

    $variant = $service->createVariant($product, [
      'name' => 'Red Variant',
      'sku' => 'RED-001',
      'attribute_value_ids' => [
        $attributeValue->id,
      ],
      'price' => 100,
      'stock' => 10,
      'is_active' => true,
    ]);

    $this->assertDatabaseHas('product_variants', [
      'id' => $variant->id,
      'product_id' => $product->id,
      'sku' => 'RED-001',
    ]);

    $this->assertDatabaseHas('product_variant_attribute_values', [
      'product_variant_id' => $variant->id,
      'attribute_value_id' => $attributeValue->id,
    ]);

    $this->assertNotEmpty($variant->combination_hash);
  }

  public function test_duplicate_attribute_combination_cannot_be_created(): void
  {
    $product = Product::create([
      'name' => 'Variable Product',
      'slug' => 'variable-product',
      'type' => 'variable',
      'price' => null,
      'stock' => null,
      'is_active' => true,
    ]);

    $attribute = ProductAttribute::create([
      'name' => 'Color',
      'slug' => 'color',
      'sort_order' => 0,
      'is_active' => true,
    ]);

    $product->productAttributes()->attach($attribute->id);

    $attributeValue = AttributeValue::create([
      'product_attribute_id' => $attribute->id,
      'value' => 'Red',
      'slug' => 'red',
      'sort_order' => 0,
      'is_active' => true,
    ]);

    $service = app(ProductVariantService::class);

    $service->createVariant($product, [
      'name' => 'Red Variant',
      'sku' => 'RED-001',
      'attribute_value_ids' => [
        $attributeValue->id,
      ],
      'price' => 100,
      'stock' => 10,
      'is_active' => true,
    ]);

    $this->expectException(
      DuplicateVariantCombinationException::class
    );

    $service->createVariant($product, [
      'name' => 'Another Red Variant',
      'sku' => 'RED-002',
      'attribute_value_ids' => [
        $attributeValue->id,
      ],
      'price' => 120,
      'stock' => 5,
      'is_active' => true,
    ]);
  }
}
