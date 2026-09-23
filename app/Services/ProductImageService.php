<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProductImageService
{
    public function createImage(
        Product $product,
        array $data
    ): ProductImage {
        /** @var UploadedFile $image */
        $image = $data['image'];

        $imagePath = $image->store(
            'products/' . $product->id,
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

                $isPrimary = $this->isPrimaryValue(
                    $data['is_primary'] ?? false
                );

                if ($isPrimary) {
                    $this->unsetPrimaryImage(
                        $lockedProduct
                    );
                }

                return $lockedProduct->images()->create([
                    'image_path' => $imagePath,
                    'alt_text' => $data['alt_text'] ?? null,
                    'sort_order' => $data['sort_order'] ?? 0,
                    'is_primary' => $isPrimary,
                ]);
            });

            return $productImage;
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($imagePath);

            throw $exception;
        }
    }

    public function updateImage(
        ProductImage $productImage,
        array $data
    ): ProductImage {
        $oldImagePath = $productImage->image_path;
        $newImagePath = null;

        if (isset($data['image'])) {
            /** @var UploadedFile $image */
            $image = $data['image'];

            $newImagePath = $image->store(
                'products/' . $productImage->product_id,
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

                    'alt_text' => array_key_exists(
                        'alt_text',
                        $data
                    )
                        ? $data['alt_text']
                        : $lockedImage->alt_text,

                    'sort_order' => array_key_exists(
                        'sort_order',
                        $data
                    )
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

    public function deleteImage(
        ProductImage $productImage
    ): void {
        $imagePath = $productImage->image_path;

        DB::transaction(function () use ($productImage) {
            $productImage->delete();
        });

        Storage::disk('public')->delete($imagePath);
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
