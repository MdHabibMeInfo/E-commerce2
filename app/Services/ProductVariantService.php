<?php

namespace App\Services;

use App\Exceptions\DuplicateVariantCombinationException;
use App\Exceptions\InvalidVariantAttributeValueException;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductVariantService
{
    /**
     * Generate a deterministic hash for an attribute combination.
     */
    private function generateCombinationHash(
        Collection $attributeValues
    ): string {
        $attributeValueIds = $attributeValues
            ->pluck('id')
            ->sort()
            ->values()
            ->all();

        return hash(
            'sha256',
            implode(':', $attributeValueIds)
        );
    }

    private function validateProductCanHaveVariants(
        Product $product
    ): void {
        if ($product->type !== 'variable') {
            throw ValidationException::withMessages([
                'product' => [
                    'Only variable products can have variants.',
                ],
            ]);
        }

        if (! $product->productAttributes()->exists()) {
            throw ValidationException::withMessages([
                'attribute_value_ids' => [
                    'At least one product attribute must be assigned before creating a variant.',
                ],
            ]);
        }
    }

    /**
     * Get attribute values by their IDs.
     */
    public function getAttributeValues(
        array $attributeValueIds
    ): Collection {
        return AttributeValue::query()
            ->whereIn('id', $attributeValueIds)
            ->with('productAttribute')
            ->get();
    }

    /**
     * Validate that all attribute values belong
     * to the product's allowed attributes.
     */
    public function validateAttributeValuesBelongToProduct(
        Product $product,
        Collection $attributeValues
    ): void {
        $allowedAttributeIds = $product
            ->productAttributes()
            ->pluck('product_attributes.id');

        $invalidAttributeValues = $attributeValues
            ->filter(
                fn (AttributeValue $attributeValue) => ! $allowedAttributeIds->contains(
                    $attributeValue->product_attribute_id
                )
            );

        if ($invalidAttributeValues->isNotEmpty()) {
            throw new InvalidVariantAttributeValueException(
                'One or more attribute values are not allowed for this product.'
            );
        }
    }

    /**
     * Validate that a variant does not contain
     * multiple values from the same attribute.
     */
    public function validateNoDuplicateAttributes(
        Collection $attributeValues
    ): void {
        $attributeIds = $attributeValues
            ->pluck('product_attribute_id');

        if ($attributeIds->count() !== $attributeIds->unique()->count()) {
            throw new InvalidVariantAttributeValueException(
                'A variant cannot contain multiple values from the same attribute.'
            );
        }
    }

    /**
     * Validate that the attribute combination
     * does not already exist for the product.
     */
    public function validateUniqueCombination(
        Product $product,
        Collection $attributeValues
    ): void {
        $combinationHash = $this->generateCombinationHash(
            $attributeValues
        );

        $exists = $product->variants()
            ->where('combination_hash', $combinationHash)
            ->exists();

        if ($exists) {
            throw new DuplicateVariantCombinationException(
                'A variant with the same attribute combination already exists for this product.'
            );
        }
    }

    /**
     * Validate that all requested attribute values exist.
     */
    public function validateAllAttributeValuesExist(
        array $attributeValueIds,
        Collection $attributeValues
    ): void {
        if (count($attributeValueIds) !== $attributeValues->count()) {
            throw new InvalidVariantAttributeValueException(
                'One or more attribute values do not exist.'
            );
        }
    }

    public function validateActiveAttributeValues(
        Collection $attributeValues
    ): void {
        foreach ($attributeValues as $attributeValue) {
            if (! $attributeValue->is_active) {
                throw new InvalidVariantAttributeValueException(
                    "Attribute value '{$attributeValue->value}' is inactive."
                );
            }

            if (! $attributeValue->productAttribute->is_active) {
                throw new InvalidVariantAttributeValueException(
                    "Attribute '{$attributeValue->productAttribute->name}' is inactive."
                );
            }
        }
    }

    public function validateUniqueCombinationForUpdate(
        Product $product,
        ProductVariant $variant,
        string $combinationHash
    ): void {
        $exists = $product->variants()
            ->where('combination_hash', $combinationHash)
            ->where('id', '!=', $variant->id)
            ->exists();

        if ($exists) {
            throw new DuplicateVariantCombinationException(
                'A variant with the same attribute combination already exists for this product.'
            );
        }
    }

    /**
     * Create a new product variant with its attribute values.
     */
    public function createVariant(
        Product $product,
        array $data
    ): ProductVariant {

        $this->validateProductCanHaveVariants($product);

        $attributeValueIds = $data['attribute_value_ids'];

        if ($product->type !== 'variable') {
            throw ValidationException::withMessages([
                'product' => [
                    'Only variable products can have variants.',
                ],
            ]);
        }
        $attributeValueIds = $data['attribute_value_ids'];

        $attributeValues = $this->getAttributeValues(
            $attributeValueIds
        );

        $this->validateAllAttributeValuesExist(
            $attributeValueIds,
            $attributeValues
        );

        $this->validateAttributeValuesBelongToProduct(
            $product,
            $attributeValues
        );

        $this->validateActiveAttributeValues(
            $attributeValues
        );

        $this->validateNoDuplicateAttributes(
            $attributeValues
        );

        $this->validateUniqueCombination(
            $product,
            $attributeValues
        );

        $combinationHash = $this->generateCombinationHash(
            $attributeValues
        );

        return DB::transaction(function () use (
            $product,
            $data,
            $combinationHash,
            $attributeValues
        ) {
            $variant = $product->variants()->create([
                'name' => $data['name'] ?? null,
                'sku' => $data['sku'],
                'combination_hash' => $combinationHash,
                'price' => $data['price'],
                'compare_at_price' => $data['compare_at_price'] ?? null,
                'stock' => $data['stock'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            $variant->attributeValues()->attach(
                $attributeValues->pluck('id')
            );

            return $variant->load('attributeValues');
        });
    }

    public function updateVariant(
        Product $product,
        ProductVariant $variant,
        array $data
    ): ProductVariant {
        $this->validateProductCanHaveVariants($product);

        $attributeValueIds = $data['attribute_value_ids'];
        // if ($variant->product_id !== $product->id) {
        //     throw new InvalidVariantAttributeValueException(
        //         'The variant does not belong to this product.'
        //     );
        // }

        $attributeValueIds = $data['attribute_value_ids'];

        $attributeValues = $this->getAttributeValues(
            $attributeValueIds
        );

        $this->validateAllAttributeValuesExist(
            $attributeValueIds,
            $attributeValues
        );

        $this->validateAttributeValuesBelongToProduct(
            $product,
            $attributeValues
        );

        $this->validateActiveAttributeValues(
            $attributeValues
        );

        $this->validateNoDuplicateAttributes(
            $attributeValues
        );

        $combinationHash = $this->generateCombinationHash(
            $attributeValues
        );

        $this->validateUniqueCombinationForUpdate(
            $product,
            $variant,
            $combinationHash
        );

        return DB::transaction(function () use (
            $variant,
            $data,
            $combinationHash,
            $attributeValues
        ) {
            $variant->update([
                'name' => $data['name'] ?? null,
                'sku' => $data['sku'],
                'combination_hash' => $combinationHash,
                'price' => $data['price'],
                'compare_at_price' => $data['compare_at_price'] ?? null,
                'stock' => $data['stock'],
                'is_active' => $data['is_active'] ?? $variant->is_active,
            ]);

            $variant->attributeValues()->sync(
                $attributeValues->pluck('id')
            );

            return $variant->load('attributeValues');
        });
    }

    /**
     * Delete a product variant.
     */
    public function deleteVariant(
        ProductVariant $variant
    ): void {
        DB::transaction(function () use ($variant) {
            $variant->delete();
        });
    }

    public function restoreVariant(
        ProductVariant $variant
    ): ProductVariant {
        if (! $variant->trashed()) {
            throw ValidationException::withMessages([
                'variant' => [
                    'This variant is not deleted.',
                ],
            ]);
        }

        return DB::transaction(function () use ($variant) {
            $variant->restore();

            return $variant->refresh();
        });
    }
}
