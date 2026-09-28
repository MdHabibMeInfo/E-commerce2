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

    public function test_multiple_images_can_be_created_with_first_as_primary(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'name' => 'Multi Image Product',
            'slug' => 'multi-image-product',
            'type' => 'simple',
            'price' => 150,
            'stock' => 5,
            'is_active' => true,
        ]);

        $service = app(ProductImageService::class);

        $images = [
            UploadedFile::fake()->image('img1.jpg'),
            UploadedFile::fake()->image('img2.jpg'),
            UploadedFile::fake()->image('img3.jpg'),
        ];

        $created = $service->createMultipleImages(
            $product,
            $images,
            null,
            ['Image 1', 'Image 2', 'Image 3']
        );

        $this->assertCount(3, $created);
        $this->assertTrue($created[0]->is_primary);
        $this->assertFalse($created[1]->is_primary);
        $this->assertFalse($created[2]->is_primary);
        $this->assertEquals(0, $created[0]->sort_order);
        $this->assertEquals(1, $created[1]->sort_order);
        $this->assertEquals(2, $created[2]->sort_order);

        foreach ($created as $img) {
            Storage::disk('public')->assertExists($img->image_path);
            $this->assertDatabaseHas('product_images', [
                'id' => $img->id,
                'product_id' => $product->id,
            ]);
        }
    }

    public function test_multiple_images_can_be_created_with_specified_primary_index(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'name' => 'Multi Image Product 2',
            'slug' => 'multi-image-product-2',
            'type' => 'simple',
            'price' => 150,
            'stock' => 5,
            'is_active' => true,
        ]);

        $service = app(ProductImageService::class);

        $images = [
            UploadedFile::fake()->image('img1.jpg'),
            UploadedFile::fake()->image('img2.jpg'),
        ];

        $created = $service->createMultipleImages(
            $product,
            $images,
            1
        );

        $this->assertFalse($created[0]->is_primary);
        $this->assertTrue($created[1]->is_primary);
    }

    public function test_deleting_primary_image_promotes_next_image_to_primary(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'name' => 'Auto Promote Product',
            'slug' => 'auto-promote-product',
            'type' => 'simple',
            'price' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        $service = app(ProductImageService::class);

        $firstImage = $service->createImage($product, [
            'image' => UploadedFile::fake()->image('first.jpg'),
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        $secondImage = $service->createImage($product, [
            'image' => UploadedFile::fake()->image('second.jpg'),
            'sort_order' => 1,
            'is_primary' => false,
        ]);

        $service->deleteImage($firstImage);

        $this->assertDatabaseMissing('product_images', ['id' => $firstImage->id]);
        $this->assertDatabaseHas('product_images', [
            'id' => $secondImage->id,
            'is_primary' => true,
        ]);
    }

    public function test_set_primary_image_promotes_selected_image(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'name' => 'Set Primary Product',
            'slug' => 'set-primary-product',
            'type' => 'simple',
            'price' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        $service = app(ProductImageService::class);

        $img1 = $service->createImage($product, [
            'image' => UploadedFile::fake()->image('img1.jpg'),
            'is_primary' => true,
        ]);

        $img2 = $service->createImage($product, [
            'image' => UploadedFile::fake()->image('img2.jpg'),
            'is_primary' => false,
        ]);

        $service->setPrimaryImage($img2);

        $this->assertDatabaseHas('product_images', [
            'id' => $img1->id,
            'is_primary' => false,
        ]);

        $this->assertDatabaseHas('product_images', [
            'id' => $img2->id,
            'is_primary' => true,
        ]);
    }

    public function test_reorder_images_updates_sort_orders(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'name' => 'Reorder Product',
            'slug' => 'reorder-product',
            'type' => 'simple',
            'price' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        $service = app(ProductImageService::class);

        $img1 = $service->createImage($product, [
            'image' => UploadedFile::fake()->image('img1.jpg'),
            'sort_order' => 0,
        ]);

        $img2 = $service->createImage($product, [
            'image' => UploadedFile::fake()->image('img2.jpg'),
            'sort_order' => 1,
        ]);

        $service->reorderImages($product, [$img2->id, $img1->id]);

        $this->assertDatabaseHas('product_images', [
            'id' => $img2->id,
            'sort_order' => 0,
        ]);

        $this->assertDatabaseHas('product_images', [
            'id' => $img1->id,
            'sort_order' => 1,
        ]);
    }
}
