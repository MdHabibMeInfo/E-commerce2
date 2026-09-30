<?php

namespace App\Services;

use App\Models\ProductAttribute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductAttributeService
{
    public function create(array $data): ProductAttribute
    {
        $slug = $this->generateUniqueSlug(
            $data['slug'] ?? null,
            $data['name']
        );

        return DB::transaction(function () use ($data, $slug) {
            return ProductAttribute::create([
                'name' => $data['name'],
                'slug' => $slug,
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    public function update(
        ProductAttribute $productAttribute,
        array $data
    ): ProductAttribute {
        $slug = $this->resolveUpdateSlug(
            $productAttribute,
            $data['slug'] ?? null
        );

        return DB::transaction(function () use (
            $productAttribute,
            $data,
            $slug
        ) {
            $productAttribute->update([
                'name' => $data['name'],
                'slug' => $slug,
                'sort_order' => $data['sort_order']
                    ?? $productAttribute->sort_order,
                'is_active' => $data['is_active']
                    ?? $productAttribute->is_active,
            ]);

            return $productAttribute->refresh();
        });
    }

    public function delete(ProductAttribute $productAttribute): void
    {
        if ($productAttribute->values()->withTrashed()->exists() || $productAttribute->products()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'product_attribute' => [
                    'This product attribute cannot be deleted while it is assigned to products or has values.',
                ],
            ]);
        }

        $productAttribute->delete();
    }

    public function restore(
        ProductAttribute $productAttribute
    ): ProductAttribute {
        if (! $productAttribute->trashed()) {
            throw ValidationException::withMessages([
                'product_attribute' => [
                    'This product attribute is not deleted.',
                ],
            ]);
        }

        return DB::transaction(function () use ($productAttribute) {
            $productAttribute->restore();

            return $productAttribute->refresh();
        });
    }

    private function generateUniqueSlug(
        ?string $requestedSlug,
        string $name
    ): string {
        $baseSlug = Str::slug(
            trim((string) ($requestedSlug ?? '')) ?: $name
        );

        $slug = $baseSlug;
        $counter = 2;

        while (
            ProductAttribute::withTrashed()
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function resolveUpdateSlug(
        ProductAttribute $productAttribute,
        ?string $requestedSlug
    ): string {
        if ($requestedSlug === null) {
            return $productAttribute->slug;
        }

        $normalizedSlug = trim((string) $requestedSlug);

        if ($normalizedSlug === '') {
            return $productAttribute->slug;
        }

        $baseSlug = Str::slug($normalizedSlug);

        $slug = $baseSlug;
        $counter = 2;

        while (
            ProductAttribute::withTrashed()
                ->where('slug', $slug)
                ->where('id', '!=', $productAttribute->id)
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
