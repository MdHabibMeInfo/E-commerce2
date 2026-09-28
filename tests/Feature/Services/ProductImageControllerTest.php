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

    public function test_authorized_user_can_upload_multiple_product_images(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        Permission::findOrCreate('products.update', 'web');
        Permission::findOrCreate('product-images.create', 'web');

        $user->givePermissionTo(['products.update', 'product-images.create']);

        $product = Product::create([
            'name' => 'API Multi Image Product',
            'slug' => 'api-multi-image-product',
            'type' => 'simple',
            'price' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->post(
                "/api/products/{$product->id}/images",
                [
                    'images' => [
                        UploadedFile::fake()->image('img1.jpg'),
                        UploadedFile::fake()->image('img2.jpg'),
                    ],
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Product images uploaded successfully.');

        $this->assertDatabaseCount('product_images', 2);
    }

    public function test_authorized_user_can_set_primary_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        Permission::findOrCreate('products.update', 'web');
        Permission::findOrCreate('product-images.update', 'web');

        $user->givePermissionTo(['products.update', 'product-images.update']);

        $product = Product::create([
            'name' => 'Set Primary API',
            'slug' => 'set-primary-api',
            'type' => 'simple',
            'price' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        $img1 = $product->images()->create([
            'image_path' => 'products/1/img1.jpg',
            'is_primary' => true,
        ]);

        $img2 = $product->images()->create([
            'image_path' => 'products/1/img2.jpg',
            'is_primary' => false,
        ]);

        $response = $this->actingAs($user)
            ->patch("/api/products/{$product->id}/images/{$img2->id}/primary");

        $response->assertOk()
            ->assertJsonPath('message', 'Product primary image set successfully.');

        $this->assertDatabaseHas('product_images', [
            'id' => $img1->id,
            'is_primary' => false,
        ]);

        $this->assertDatabaseHas('product_images', [
            'id' => $img2->id,
            'is_primary' => true,
        ]);
    }

    public function test_authorized_user_can_reorder_product_images(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        Permission::findOrCreate('products.update', 'web');
        Permission::findOrCreate('product-images.update', 'web');

        $user->givePermissionTo(['products.update', 'product-images.update']);

        $product = Product::create([
            'name' => 'Reorder API',
            'slug' => 'reorder-api',
            'type' => 'simple',
            'price' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        $img1 = $product->images()->create([
            'image_path' => 'products/1/img1.jpg',
            'sort_order' => 0,
        ]);

        $img2 = $product->images()->create([
            'image_path' => 'products/1/img2.jpg',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($user)
            ->post("/api/products/{$product->id}/images/reorder", [
                'image_ids' => [$img2->id, $img1->id],
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Product images reordered successfully.');

        $this->assertDatabaseHas('product_images', [
            'id' => $img2->id,
            'sort_order' => 0,
        ]);

        $this->assertDatabaseHas('product_images', [
            'id' => $img1->id,
            'sort_order' => 1,
        ]);
    }

    public function test_admin_web_routes_for_product_images(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $product = Product::create([
            'name' => 'Admin Product Images',
            'slug' => 'admin-product-images',
            'type' => 'simple',
            'price' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        // Upload multiple images via admin web route
        $uploadResponse = $this->actingAs($user)
            ->post(route('admin.products.images.store', $product), [
                'images' => [
                    UploadedFile::fake()->image('admin1.jpg'),
                    UploadedFile::fake()->image('admin2.jpg'),
                ],
            ]);

        $uploadResponse->assertRedirect();
        $this->assertDatabaseCount('product_images', 2);

        $img1 = $product->images()->first();
        $img2 = $product->images()->skip(1)->first();

        $this->assertTrue($img1->is_primary);
        $this->assertFalse($img2->is_primary);

        // Set primary via admin web route
        $primaryResponse = $this->actingAs($user)
            ->patch(route('admin.products.images.primary', [$product, $img2]));

        $primaryResponse->assertRedirect();
        $this->assertDatabaseHas('product_images', [
            'id' => $img2->id,
            'is_primary' => true,
        ]);

        // Delete image via admin web route
        $deleteResponse = $this->actingAs($user)
            ->delete(route('admin.products.images.destroy', [$product, $img2]));

        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('product_images', [
            'id' => $img2->id,
        ]);
        // Since img2 was primary, img1 should be promoted to primary
        $this->assertDatabaseHas('product_images', [
            'id' => $img1->id,
            'is_primary' => true,
        ]);
    }
}
