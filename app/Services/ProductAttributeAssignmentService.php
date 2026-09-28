<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductAttribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductAttributeAssignmentService
{
    private function validateAttributesCanBeDetached(
        Product $product,
        Collection $attributeIds
    ): void {
        if ($attributeIds->isEmpty()) {
            return;
        }

        $usedAttributeIds = $product
            ->variants()
            ->whereHas(
                'attributeValues',
                function ($query) use ($attributeIds) {
                    $query->whereIn(
                        'product_attribute_id',
                        $attributeIds
                    );
                }
            )
            ->with('attributeValues')
            ->get()
            ->flatMap(
                fn ($variant) => $variant->attributeValues
            )
            ->pluck('product_attribute_id')
            ->unique();

        if ($usedAttributeIds->isNotEmpty()) {
            $attributes = ProductAttribute::query()
                ->whereIn('id', $usedAttributeIds)
                ->pluck('name', 'id');

            $names = $attributes->values()->implode(', ');

            throw ValidationException::withMessages([
                'product_attribute_ids' => [
                    "The following product attributes cannot be detached because they are used by existing product variants: {$names}.",
                ],
            ]);
        }
    }

    public function assign(
        Product $product,
        array $attributeIds
    ): Product {

        if ($product->type !== 'variable') {
            throw ValidationException::withMessages([
                'product_attribute_ids' => [
                    'Only variable products can have product attributes.',
                ],
            ]);
        }

        $attributes = ProductAttribute::query()
            ->whereIn('id', $attributeIds)
            ->get();

        $this->validateAllAttributesExist(
            $attributeIds,
            $attributes
        );

        $this->validateActiveAttributes(
            $attributes
        );

        $currentAttributeIds = $product
            ->productAttributes()
            ->pluck('product_attributes.id');

        $attributesToDetach = $currentAttributeIds
            ->diff($attributeIds);

        $this->validateAttributesCanBeDetached(
            $product,
            $attributesToDetach
        );

        return DB::transaction(function () use (
            $product,
            $attributeIds
        ) {
            $product->productAttributes()->sync(
                $attributeIds
            );

            return $product->load('productAttributes');
        });
    }

    private function validateAllAttributesExist(
        array $attributeIds,
        Collection $attributes
    ): void {
        if (count($attributeIds) !== $attributes->count()) {
            throw ValidationException::withMessages([
                'product_attribute_ids' => [
                    'One or more selected product attributes do not exist.',
                ],
            ]);
        }
    }

    private function validateActiveAttributes(
        Collection $attributes
    ): void {
        foreach ($attributes as $attribute) {
            if (! $attribute->is_active) {
                throw ValidationException::withMessages([
                    'product_attribute_ids' => [
                        "Product attribute '{$attribute->name}' is inactive.",
                    ],
                ]);
            }
        }
    }
}
