<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProductImageService
{
    /**
     * Create a single product image.
     *
     * @param  array{image: UploadedFile, alt_text?: ?string, sort_order?: ?int, is_primary?: bool}  $data
     */
    public function createImage(
        Product $product,
        array $data
    ): ProductImage {
        /** @var UploadedFile $image */
        $image = $data['image'];

        $imagePath = $image->store(
            'products/'.$product->id,
            'public'
        );

        try {
            $productImage = DB::transaction(function () use (
                $product,
                $data,
                $imagePath
            ) {
                $lockedProduct = Product::query()
                    ->whereKey($product->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $hasExistingPrimary = $lockedProduct->images()
                    ->where('is_primary', true)
                    ->exists();

                $isPrimary = array_key_exists('is_primary', $data)
                    ? $this->isPrimaryValue($data['is_primary'])
                    : ! $hasExistingPrimary;

                if ($isPrimary) {
                    $this->unsetPrimaryImage($lockedProduct);
                }

                $sortOrder = array_key_exists('sort_order', $data) && $data['sort_order'] !== null
                    ? (int) $data['sort_order']
                    : (int) (($lockedProduct->images()->max('sort_order') ?? -1) + 1);

                return $lockedProduct->images()->create([
                    'image_path' => $imagePath,
                    'alt_text' => $data['alt_text'] ?? null,
                    'sort_order' => $sortOrder,
                    'is_primary' => $isPrimary,
                ]);
            });

            return $productImage;
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($imagePath);

            throw $exception;
        }
    }

    /**
     * Upload and store multiple images for a product.
     *
     * @param  array<int, UploadedFile>  $images
     * @param  array<int, string|null>  $altTexts
     * @return Collection<int, ProductImage>
     */
    public function createMultipleImages(
        Product $product,
        array $images,
        ?int $primaryIndex = null,
        array $altTexts = []
    ): Collection {
        $storedPaths = [];

        try {
            foreach ($images as $index => $image) {
                if ($image instanceof UploadedFile) {
                    $storedPaths[$index] = $image->store(
                        'products/'.$product->id,
                        'public'
                    );
                }
            }

            return DB::transaction(function () use (
                $product,
                $storedPaths,
                $primaryIndex,
                $altTexts
            ) {
                $lockedProduct = Product::query()
                    ->whereKey($product->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $hasPrimary = $lockedProduct->images()
                    ->where('is_primary', true)
                    ->exists();

                $maxSortOrder = (int) ($lockedProduct->images()->max('sort_order') ?? -1);
                $nextSort = $maxSortOrder + 1;

                $createdImages = new Collection;

                foreach ($storedPaths as $index => $path) {
                    $isPrimary = false;

                    if ($primaryIndex !== null) {
                        $isPrimary = ($index === $primaryIndex);
                    } elseif (! $hasPrimary && $index === 0) {
                        $isPrimary = true;
                        $hasPrimary = true;
                    }

                    if ($isPrimary) {
                        $this->unsetPrimaryImage($lockedProduct);
                    }

                    $createdImages->push(
                        $lockedProduct->images()->create([
                            'image_path' => $path,
                            'alt_text' => $altTexts[$index] ?? null,
                            'sort_order' => $nextSort++,
                            'is_primary' => $isPrimary,
                        ])
                    );
                }

                return $createdImages;
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }
    }

    /**
     * Update a product image metadata and/or file.
     *
     * @param  array{image?: UploadedFile, alt_text?: ?string, sort_order?: ?int, is_primary?: bool}  $data
     */
    public function updateImage(
        ProductImage $productImage,
        array $data
    ): ProductImage {
        $oldImagePath = $productImage->image_path;
        $newImagePath = null;

        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            /** @var UploadedFile $image */
            $image = $data['image'];

            $newImagePath = $image->store(
                'products/'.$productImage->product_id,
                'public'
            );
        }

        try {
            $updatedImage = DB::transaction(function () use (
                $productImage,
                $data,
                $newImagePath
            ) {
                $lockedProduct = Product::query()
                    ->whereKey($productImage->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedImage = ProductImage::query()
                    ->whereKey($productImage->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (array_key_exists('is_primary', $data)) {
                    $isPrimary = $this->isPrimaryValue(
                        $data['is_primary']
                    );

                    if ($isPrimary) {
                        $this->unsetPrimaryImage(
                            $lockedProduct,
                            $lockedImage->id
                        );
                    }
                } else {
                    $isPrimary = $lockedImage->is_primary;
                }

                $lockedImage->update([
                    'image_path' => $newImagePath
                        ?? $lockedImage->image_path,

                    'alt_text' => array_key_exists('alt_text', $data)
                        ? $data['alt_text']
                        : $lockedImage->alt_text,

                    'sort_order' => array_key_exists('sort_order', $data)
                        ? $data['sort_order']
                        : $lockedImage->sort_order,

                    'is_primary' => $isPrimary,
                ]);

                return $lockedImage->fresh();
            });

            if (
                $newImagePath !== null
                && $oldImagePath !== $newImagePath
            ) {
                Storage::disk('public')->delete(
                    $oldImagePath
                );
            }

            return $updatedImage;
        } catch (Throwable $exception) {
            if ($newImagePath !== null) {
                Storage::disk('public')->delete(
                    $newImagePath
                );
            }

            throw $exception;
        }
    }

    /**
     * Directly promote an image to primary for its product.
     */
    public function setPrimaryImage(ProductImage $productImage): ProductImage
    {
        return DB::transaction(function () use ($productImage) {
            $lockedProduct = Product::query()
                ->whereKey($productImage->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedImage = ProductImage::query()
                ->whereKey($productImage->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->unsetPrimaryImage($lockedProduct, $lockedImage->id);

            $lockedImage->update([
                'is_primary' => true,
            ]);

            return $lockedImage->fresh();
        });
    }

    /**
     * Delete an image and automatically assign a new primary if needed.
     */
    public function deleteImage(
        ProductImage $productImage
    ): void {
        $imagePath = $productImage->image_path;

        DB::transaction(function () use ($productImage) {
            $lockedProduct = Product::query()
                ->whereKey($productImage->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedImage = ProductImage::query()
                ->whereKey($productImage->id)
                ->lockForUpdate()
                ->firstOrFail();

            $wasPrimary = $lockedImage->is_primary;

            $lockedImage->delete();

            if ($wasPrimary) {
                $nextPrimary = $lockedProduct->images()
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->first();

                if ($nextPrimary !== null) {
                    $nextPrimary->update([
                        'is_primary' => true,
                    ]);
                }
            }
        });

        Storage::disk('public')->delete($imagePath);
    }

    /**
     * Reorder images by their IDs.
     *
     * @param  array<int, int>  $orderedImageIds
     */
    public function reorderImages(Product $product, array $orderedImageIds): void
    {
        DB::transaction(function () use ($product, $orderedImageIds) {
            $lockedProduct = Product::query()
                ->whereKey($product->id)
                ->lockForUpdate()
                ->firstOrFail();

            foreach ($orderedImageIds as $index => $imageId) {
                $lockedProduct->images()
                    ->whereKey($imageId)
                    ->update([
                        'sort_order' => $index,
                    ]);
            }
        });
    }

    /**
     * Delete all images of a product and clean up its storage directory.
     */
    public function deleteProductImages(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $product->images()->delete();
        });

        Storage::disk('public')->deleteDirectory('products/'.$product->id);
    }

    private function unsetPrimaryImage(
        Product $product,
        ?int $exceptImageId = null
    ): void {
        $query = $product->images()
            ->where('is_primary', true);

        if ($exceptImageId !== null) {
            $query->whereKeyNot($exceptImageId);
        }

        $query->update([
            'is_primary' => false,
        ]);
    }

    private function isPrimaryValue(mixed $value): bool
    {
        return filter_var(
            $value,
            FILTER_VALIDATE_BOOLEAN
        );
    }
}
