<?php

namespace Tests\Feature\Services;

use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use App\Services\ProductAttributeAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductAttributeAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_simple_product_cannot_have_product_attributes(): void
    {
        $product = Product::create([
            'name' => 'Simple Product',
            'slug' => 'simple-product',
            'type' => 'simple',
            'price' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        $attribute = ProductAttribute::create([
            'name' => 'Color',
            'slug' => 'color',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $service = app(ProductAttributeAssignmentService::class);

        $this->expectException(ValidationException::class);

        $service->assign(
            $product,
            [$attribute->id]
        );
    }

    public function test_variable_product_can_have_product_attributes(): void
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

        $service = app(ProductAttributeAssignmentService::class);

        $updatedProduct = $service->assign(
            $product,
            [$attribute->id]
        );

        $this->assertTrue(
            $updatedProduct->productAttributes->contains(
                'id',
                $attribute->id
            )
        );

        $this->assertDatabaseHas('product_product_attribute', [
            'product_id' => $product->id,
            'product_attribute_id' => $attribute->id,
        ]);
    }

    public function test_attribute_used_by_existing_variant_cannot_be_detached(): void
    {
        $product = Product::create([
            'name' => 'Variable Product',
            'slug' => 'variable-product',
            'type' => 'variable',
            'price' => null,
            'stock' => null,
            'is_active' => true,
        ]);

        $colorAttribute = ProductAttribute::create([
            'name' => 'Color',
            'slug' => 'color',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $sizeAttribute = ProductAttribute::create([
            'name' => 'Size',
            'slug' => 'size',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $product->productAttributes()->sync([
            $colorAttribute->id,
            $sizeAttribute->id,
        ]);

        $redValue = AttributeValue::create([
            'product_attribute_id' => $colorAttribute->id,
            'value' => 'Red',
            'slug' => 'red',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $largeValue = AttributeValue::create([
            'product_attribute_id' => $sizeAttribute->id,
            'value' => 'Large',
            'slug' => 'large',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Red / Large',
            'sku' => 'RED-LARGE',
            'combination_hash' => hash(
                'sha256',
                implode(':', collect([
                    $redValue->id,
                    $largeValue->id,
                ])->sort()->values()->all())
            ),
            'price' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        $variant->attributeValues()->attach([
            $redValue->id,
            $largeValue->id,
        ]);

        $service = app(ProductAttributeAssignmentService::class);

        $this->expectException(ValidationException::class);

        $service->assign(
            $product,
            [$sizeAttribute->id]
        );
    }
}
