<?php

namespace Tests\Feature\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductImageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_upload_product_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        Permission::findOrCreate(
            'products.update',
            'web'
        );

        Permission::findOrCreate(
            'product-images.create',
            'web'
        );

        $user->givePermissionTo([
            'products.update',
            'product-images.create',
        ]);

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

        $response = $this->actingAs($user)
            ->post(
                "/api/products/{$product->id}/images",
                [
                    'image' => $image,
                    'alt_text' => 'Test product image',
                    'sort_order' => 0,
                    'is_primary' => true,
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Product image uploaded successfully.'
            );

        $this->assertDatabaseHas('product_images', [
            'product_id' => $product->id,
            'alt_text' => 'Test product image',
            'sort_order' => 0,
            'is_primary' => true,
        ]);

        $productImage = $product
            ->images()
            ->first();

        $this->assertNotNull($productImage);

        Storage::disk('public')->assertExists(
            $productImage->image_path
        );
    }

    public function test_user_without_product_update_permission_cannot_upload_product_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        Permission::findOrCreate(
            'product-images.create',
            'web'
        );

        $user->givePermissionTo(
            'product-images.create'
        );

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

        $response = $this->actingAs($user)
            ->post(
                "/api/products/{$product->id}/images",
                [
                    'image' => $image,
                    'alt_text' => 'Test product image',
                    'sort_order' => 0,
                    'is_primary' => true,
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseCount(
            'product_images',
            0
        );

        Storage::disk('public')->assertDirectoryEmpty(
            "products/{$product->id}"
        );
    }

    public function test_user_without_product_image_create_permission_cannot_upload_product_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        Permission::findOrCreate(
            'products.update',
            'web'
        );

        $user->givePermissionTo(
            'products.update'
        );

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

        $response = $this->actingAs($user)
            ->post(
                "/api/products/{$product->id}/images",
                [
                    'image' => $image,
                    'alt_text' => 'Test product image',
                    'sort_order' => 0,
                    'is_primary' => true,
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseCount(
            'product_images',
            0
        );

        Storage::disk('public')->assertDirectoryEmpty(
            "products/{$product->id}"
        );
    }

    public function test_unauthenticated_user_cannot_upload_product_image(): void
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

        $response = $this->post(
            "/api/products/{$product->id}/images",
            [
                'image' => $image,
                'alt_text' => 'Test product image',
                'sort_order' => 0,
                'is_primary' => true,
            ]
        );

        $response->assertUnauthorized();

        $this->assertDatabaseCount(
            'product_images',
            0
        );

        Storage::disk('public')->assertDirectoryEmpty(
            "products/{$product->id}"
        );
    }
}
