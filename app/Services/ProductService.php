<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductService
{

  private function validateProductTypeUpdate(
    Product $product,
    array $data
  ): void {
    if ($product->type !== $data['type']) {
      throw ValidationException::withMessages([
        'type' => [
          'Product type cannot be changed after the product is created.',
        ],
      ]);
    }
  }

  private function resolveUpdateSlug(
    Product $product,
    ?string $requestedSlug,
  ): string {
    if ($requestedSlug === null) {
      return $product->slug;
    }

    $baseSlug = Str::slug($requestedSlug);

    $slug = $baseSlug;
    $counter = 2;

    while (
      Product::withTrashed()
      ->where('slug', $slug)
      ->where('id', '!=', $product->id)
      ->exists()
    ) {
      $slug = "{$baseSlug}-{$counter}";
      $counter++;
    }

    return $slug;
  }

  public function create(array $data): Product
  {
    $categoryId = $data['category_id'] ?? null;

    if ($categoryId !== null) {
      $this->validateCategory($categoryId);
    }

    $this->validateProductTypeData($data);

    $slug = $this->generateUniqueSlug(
      $data['slug'] ?? null,
      $data['name']
    );

    return DB::transaction(function () use (
      $data,
      $categoryId,
      $slug
    ) {
      return Product::create([
        'category_id' => $categoryId,
        'name' => $data['name'],
        'slug' => $slug,
        'sku' => $data['sku'] ?? null,
        'short_description' => $data['short_description'] ?? null,
        'description' => $data['description'] ?? null,
        'price' => $data['price'] ?? null,
        'compare_at_price' => $data['compare_at_price'] ?? null,
        'stock' => $data['stock'] ?? null,
        'type' => $data['type'],
        'is_active' => $data['is_active'] ?? true,
      ]);
    });
  }

  private function validateProductTypeData(array $data): void
  {
    $type = $data['type'];

    if ($type === 'simple') {
      if (! array_key_exists('price', $data) || $data['price'] === null) {
        throw ValidationException::withMessages([
          'price' => [
            'Price is required for a simple product.',
          ],
        ]);
      }

      if (! array_key_exists('stock', $data) || $data['stock'] === null) {
        throw ValidationException::withMessages([
          'stock' => [
            'Stock is required for a simple product.',
          ],
        ]);
      }

      if (
        ($data['compare_at_price'] ?? null) !== null
        && $data['compare_at_price'] <= $data['price']
      ) {
        throw ValidationException::withMessages([
          'compare_at_price' => [
            'Compare at price must be greater than the product price.',
          ],
        ]);
      }

      return;
    }

    if ($type === 'variable') {
      if (
        array_key_exists('price', $data)
        && $data['price'] !== null
      ) {
        throw ValidationException::withMessages([
          'price' => [
            'Price must be null for a variable product.',
          ],
        ]);
      }

      if (
        array_key_exists('stock', $data)
        && $data['stock'] !== null
      ) {
        throw ValidationException::withMessages([
          'stock' => [
            'Stock must be null for a variable product.',
          ],
        ]);
      }

      if (
        array_key_exists('compare_at_price', $data)
        && $data['compare_at_price'] !== null
      ) {
        throw ValidationException::withMessages([
          'compare_at_price' => [
            'Compare at price must be null for a variable product.',
          ],
        ]);
      }
    }
  }

  private function validateCategory(int $categoryId): void
  {
    $category = Category::query()
      ->whereKey($categoryId)
      ->whereNull('deleted_at')
      ->first();

    if (! $category) {
      throw ValidationException::withMessages([
        'category_id' => [
          'The selected category is invalid or deleted.',
        ],
      ]);
    }
  }

  private function generateUniqueSlug(
    ?string $requestedSlug,
    string $name
  ): string {
    $baseSlug = Str::slug(
      $requestedSlug ?: $name
    );

    $slug = $baseSlug;
    $counter = 2;

    while (
      Product::withTrashed()
      ->where('slug', $slug)
      ->exists()
    ) {
      $slug = "{$baseSlug}-{$counter}";
      $counter++;
    }

    return $slug;
  }

  public function update(
    Product $product,
    array $data
  ): Product {
    $categoryId = $data['category_id'] ?? null;

    if ($categoryId !== null) {
      $this->validateCategory($categoryId);
    }

    $this->validateProductTypeUpdate(
      $product,
      $data
    );

    $this->validateProductTypeData($data);

    $slug = $this->resolveUpdateSlug(
      $product,
      $data['slug'] ?? null
    );

    return DB::transaction(function () use (
      $product,
      $data,
      $categoryId,
      $slug
    ) {
      $product->update([
        'category_id' => $categoryId,
        'name' => $data['name'],
        'slug' => $slug,
        'sku' => $data['sku'] ?? null,
        'short_description' => $data['short_description'] ?? null,
        'description' => $data['description'] ?? null,
        'price' => $data['price'] ?? null,
        'compare_at_price' => $data['compare_at_price'] ?? null,
        'stock' => $data['stock'] ?? null,
        'type' => $data['type'],
        'is_active' => $data['is_active'] ?? $product->is_active,
      ]);

      return $product->refresh();
    });
  }

  public function delete(Product $product): void
  {
    $product->delete();
  }

  public function restore(Product $product): Product
  {
    if (! $product->trashed()) {
      throw ValidationException::withMessages([
        'product' => [
          'This product is not deleted.',
        ],
      ]);
    }

    if ($product->category_id !== null) {
      $category = Category::query()
        ->whereKey($product->category_id)
        ->whereNull('deleted_at')
        ->first();

      if (! $category) {
        throw ValidationException::withMessages([
          'category_id' => [
            'The product category is deleted or no longer exists.',
          ],
        ]);
      }
    }

    return DB::transaction(function () use ($product) {
      $product->restore();

      return $product->refresh();
    });
  }
}
