<?php

namespace App\Services;

use App\Models\AttributeValue;
use App\Models\ProductAttribute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttributeValueService
{
    public function create(array $data): AttributeValue
    {
        $productAttributeId = $data['product_attribute_id'];

        $this->validateProductAttribute(
            $productAttributeId
        );

        $slug = $this->generateUniqueSlug(
            $data['slug'] ?? null,
            $data['value'],
            $productAttributeId
        );

        return DB::transaction(function () use (
            $data,
            $productAttributeId,
            $slug
        ) {
            return AttributeValue::create([
                'product_attribute_id' => $productAttributeId,
                'value' => $data['value'],
                'slug' => $slug,
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    public function update(
        AttributeValue $attributeValue,
        array $data
    ): AttributeValue {
        $currentProductAttributeId =
            $attributeValue->product_attribute_id;

        $newProductAttributeId =
            $data['product_attribute_id'];

        $parentChanged =
            $currentProductAttributeId !== $newProductAttributeId;

        /*
        |--------------------------------------------------------------------------
        | Validate new parent attribute
        |--------------------------------------------------------------------------
        */

        $this->validateProductAttribute(
            $newProductAttributeId
        );

        /*
        |--------------------------------------------------------------------------
        | Prevent changing parent when value is already
        | being used by product variants.
        |--------------------------------------------------------------------------
        */

        if (
            $parentChanged
            && $attributeValue->variants()->exists()
        ) {
            throw ValidationException::withMessages([
                'product_attribute_id' => [
                    'The attribute cannot be changed because this value is already used by one or more product variants.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve slug
        |--------------------------------------------------------------------------
        */

        $slug = $this->resolveUpdateSlug(
            attributeValue: $attributeValue,
            requestedSlug: $data['slug'] ?? null,
            value: $data['value'],
            productAttributeId: $newProductAttributeId,
            parentChanged: $parentChanged
        );

        return DB::transaction(function () use (
            $attributeValue,
            $data,
            $newProductAttributeId,
            $slug
        ) {
            $attributeValue->update([
                'product_attribute_id' => $newProductAttributeId,
                'value' => $data['value'],
                'slug' => $slug,
                'sort_order' => $data['sort_order']
                    ?? $attributeValue->sort_order,
                'is_active' => $data['is_active']
                    ?? $attributeValue->is_active,
            ]);

            return $attributeValue->refresh();
        });
    }

    public function delete(
        AttributeValue $attributeValue
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Do not delete a value that is used by a variant.
        |--------------------------------------------------------------------------
        */

        if ($attributeValue->variants()->exists()) {
            throw ValidationException::withMessages([
                'attribute_value' => [
                    'This attribute value cannot be deleted because it is used by one or more product variants.',
                ],
            ]);
        }

        $attributeValue->delete();
    }

    public function restore(
        AttributeValue $attributeValue
    ): AttributeValue {
        if (! $attributeValue->trashed()) {
            throw ValidationException::withMessages([
                'attribute_value' => [
                    'This attribute value is not deleted.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Parent attribute must still exist and be active.
        |--------------------------------------------------------------------------
        */

        $this->validateProductAttribute(
            $attributeValue->product_attribute_id
        );

        /*
        |--------------------------------------------------------------------------
        | Make sure restoring does not conflict with another
        | existing value using the same slug.
        |--------------------------------------------------------------------------
        */

        $slugExists = AttributeValue::query()
            ->where(
                'product_attribute_id',
                $attributeValue->product_attribute_id
            )
            ->where(
                'slug',
                $attributeValue->slug
            )
            ->where('id', '!=', $attributeValue->id)
            ->exists();

        if ($slugExists) {
            throw ValidationException::withMessages([
                'slug' => [
                    'This attribute value cannot be restored because its slug is already in use.',
                ],
            ]);
        }

        return DB::transaction(function () use (
            $attributeValue
        ) {
            $attributeValue->restore();

            return $attributeValue->refresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Parent Attribute Validation
    |--------------------------------------------------------------------------
    */

    private function validateProductAttribute(
        int $productAttributeId
    ): ProductAttribute {
        $productAttribute = ProductAttribute::query()
            ->whereKey($productAttributeId)
            ->whereNull('deleted_at')
            ->first();

        if (! $productAttribute) {
            throw ValidationException::withMessages([
                'product_attribute_id' => [
                    'The selected product attribute is invalid or deleted.',
                ],
            ]);
        }

        if (! $productAttribute->is_active) {
            throw ValidationException::withMessages([
                'product_attribute_id' => [
                    'The selected product attribute is inactive.',
                ],
            ]);
        }

        return $productAttribute;
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Unique Slug
    |--------------------------------------------------------------------------
    */

    private function generateUniqueSlug(
        ?string $requestedSlug,
        string $value,
        int $productAttributeId
    ): string {
        $baseSlug = Str::slug(
            $requestedSlug ?: $value
        );

        if ($baseSlug === '') {
            throw ValidationException::withMessages([
                'slug' => [
                    'The provided value cannot be converted into a valid slug.',
                ],
            ]);
        }

        $slug = $baseSlug;
        $counter = 2;

        while (
            AttributeValue::withTrashed()
                ->where(
                    'product_attribute_id',
                    $productAttributeId
                )
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Update Slug
    |--------------------------------------------------------------------------
    */

    private function resolveUpdateSlug(
        AttributeValue $attributeValue,
        ?string $requestedSlug,
        string $value,
        int $productAttributeId,
        bool $parentChanged
    ): string {
        /*
        |--------------------------------------------------------------------------
        | Explicit slug provided
        |--------------------------------------------------------------------------
        */

        if ($requestedSlug !== null) {
            return $this->generateUniqueSlugForUpdate(
                attributeValue: $attributeValue,
                requestedSlug: $requestedSlug,
                productAttributeId: $productAttributeId
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Parent did not change.
        |
        | Keep the existing slug when slug is omitted.
        |--------------------------------------------------------------------------
        */

        if (! $parentChanged) {
            return $attributeValue->slug;
        }

        /*
        |--------------------------------------------------------------------------
        | Parent changed and slug was omitted.
        |
        | Generate a slug from the value for the new parent.
        |--------------------------------------------------------------------------
        */

        return $this->generateUniqueSlug(
            requestedSlug: null,
            value: $value,
            productAttributeId: $productAttributeId
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Unique Slug During Update
    |--------------------------------------------------------------------------
    */

    private function generateUniqueSlugForUpdate(
        AttributeValue $attributeValue,
        string $requestedSlug,
        int $productAttributeId
    ): string {
        $baseSlug = Str::slug($requestedSlug);

        if ($baseSlug === '') {
            throw ValidationException::withMessages([
                'slug' => [
                    'The provided slug cannot be converted into a valid slug.',
                ],
            ]);
        }

        $slug = $baseSlug;
        $counter = 2;

        while (
            AttributeValue::withTrashed()
                ->where(
                    'product_attribute_id',
                    $productAttributeId
                )
                ->where('slug', $slug)
                ->where('id', '!=', $attributeValue->id)
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
