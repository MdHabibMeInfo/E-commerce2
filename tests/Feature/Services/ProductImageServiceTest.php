<?php

namespace Tests\Feature\Services;

use App\Models\Product;
use App\Services\ProductImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_image_can_be_created_successfully(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'type' => 'simple',
            'price' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        $image = UploadedFile::fake()->image(
            'product.jpg'
        );

        $service = app(ProductImageService::class);

        $productImage = $service->createImage(
            $product,
            [
                'image' => $image,
                'alt_text' => 'Test product image',
                'sort_order' => 0,
                'is_primary' => true,
            ]
        );

        $this->assertDatabaseHas('product_images', [
            'id' => $productImage->id,
            'product_id' => $product->id,
            'image_path' => $productImage->image_path,
            'alt_text' => 'Test product image',
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        Storage::disk('public')->assertExists(
            $productImage->image_path
        );
    }

    public function test_creating_primary_image_unsets_existing_primary_image(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'type' => 'simple',
            'price' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        $service = app(ProductImageService::class);

        $firstImage = $service->createImage(
            $product,
            [
                'image' => UploadedFile::fake()->image(
                    'first.jpg'
                ),
                'alt_text' => 'First image',
                'sort_order' => 0,
                'is_primary' => true,
            ]
        );

        $secondImage = $service->createImage(
            $product,
            [
                'image' => UploadedFile::fake()->image(
                    'second.jpg'
                ),
                'alt_text' => 'Second image',
                'sort_order' => 1,
                'is_primary' => true,
            ]
        );

        $this->assertDatabaseHas('product_images', [
            'id' => $firstImage->id,
            'is_primary' => false,
        ]);

        $this->assertDatabaseHas('product_images', [
            'id' => $secondImage->id,
            'is_primary' => true,
        ]);
    }

    public function test_image_can_be_updated_with_a_new_file(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'type' => 'simple',
            'price' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        $service = app(ProductImageService::class);

        $originalImage = $service->createImage(
            $product,
            [
                'image' => UploadedFile::fake()->image(
                    'original.jpg'
                ),
                'alt_text' => 'Original image',
                'sort_order' => 0,
                'is_primary' => true,
            ]
        );

        $oldImagePath = $originalImage->image_path;

        Storage::disk('public')->assertExists(
            $oldImagePath
        );

        $updatedImage = $service->updateImage(
            $originalImage,
            [
                'image' => UploadedFile::fake()->image(
                    'updated.jpg'
                ),
                'alt_text' => 'Updated image',
                'sort_order' => 1,
                'is_primary' => true,
            ]
        );

        $this->assertNotSame(
            $oldImagePath,
            $updatedImage->image_path
        );

        $this->assertDatabaseHas('product_images', [
            'id' => $originalImage->id,
            'image_path' => $updatedImage->image_path,
            'alt_text' => 'Updated image',
            'sort_order' => 1,
            'is_primary' => true,
        ]);

        Storage::disk('public')->assertMissing(
            $oldImagePath
        );

        Storage::disk('public')->assertExists(
            $updatedImage->image_path
        );
    }

    public function test_image_can_be_partially_updated_without_new_file(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'type' => 'simple',
            'price' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        $service = app(ProductImageService::class);

        $productImage = $service->createImage(
            $product,
            [
                'image' => UploadedFile::fake()->image(
                    'original.jpg'
                ),
                'alt_text' => 'Original image',
                'sort_order' => 0,
                'is_primary' => false,
            ]
        );

        $originalImagePath = $productImage->image_path;

        Storage::disk('public')->assertExists(
            $originalImagePath
        );

        $updatedImage = $service->updateImage(
            $productImage,
            [
                'alt_text' => 'Updated image',
                'sort_order' => 5,
                'is_primary' => true,
            ]
        );

        $this->assertSame(
            $originalImagePath,
            $updatedImage->image_path
        );

        $this->assertDatabaseHas('product_images', [
            'id' => $productImage->id,
            'image_path' => $originalImagePath,
            'alt_text' => 'Updated image',
            'sort_order' => 5,
            'is_primary' => true,
        ]);

        Storage::disk('public')->assertExists(
            $originalImagePath
        );
    }

    public function test_image_can_be_deleted_successfully(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'type' => 'simple',
            'price' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        $service = app(ProductImageService::class);

        $productImage = $service->createImage(
            $product,
            [
                'image' => UploadedFile::fake()->image(
                    'product.jpg'
                ),
                'alt_text' => 'Product image',
                'sort_order' => 0,
                'is_primary' => false,
            ]
        );

        $imagePath = $productImage->image_path;

        Storage::disk('public')->assertExists(
            $imagePath
        );

        $service->deleteImage($productImage);

        $this->assertDatabaseMissing('product_images', [
            'id' => $productImage->id,
        ]);

        Storage::disk('public')->assertMissing(
            $imagePath
        );
    }

    public function test_updating_image_to_primary_unsets_existing_primary_image(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'type' => 'simple',
            'price' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        $service = app(ProductImageService::class);

        $firstImage = $service->createImage(
            $product,
            [
                'image' => UploadedFile::fake()->image(
                    'first.jpg'
                ),
                'alt_text' => 'First image',
                'sort_order' => 0,
                'is_primary' => true,
            ]
        );

        $secondImage = $service->createImage(
            $product,
            [
                'image' => UploadedFile::fake()->image(
                    'second.jpg'
                ),
                'alt_text' => 'Second image',
                'sort_order' => 1,
                'is_primary' => false,
            ]
        );

        $updatedImage = $service->updateImage(
            $secondImage,
            [
                'is_primary' => true,
            ]
        );

        $this->assertDatabaseHas('product_images', [
            'id' => $firstImage->id,
            'is_primary' => false,
        ]);

        $this->assertDatabaseHas('product_images', [
            'id' => $secondImage->id,
            'is_primary' => true,
        ]);

        $this->assertTrue(
            $updatedImage->is_primary
        );
    }
}
