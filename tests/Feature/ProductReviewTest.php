<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('stores a customer review and its uploaded photo', function () {
    Storage::fake('public');
    $product = createReviewTestProduct();
    $image = UploadedFile::fake()->image('review.jpg');

    $response = $this->post(route('products.reviews.store', ['product' => $product->slug]), [
        'name' => 'Amina Rahman',
        'email' => 'amina@example.com',
        'rating' => 5,
        'body' => 'Fresh and excellent quality.',
        'images' => [$image],
        'is_approved' => true,
    ]);

    $response->assertRedirect(route('products.show', ['product' => $product->slug]).'#customer-reviews');
    $response->assertSessionHas('status');
    $this->assertDatabaseHas('product_reviews', [
        'product_id' => $product->id,
        'name' => 'Amina Rahman',
        'email' => 'amina@example.com',
        'rating' => 5,
        'body' => 'Fresh and excellent quality.',
        'is_approved' => false,
    ]);

    $review = ProductReview::query()->firstOrFail();
    expect($review->is_approved)->toBeFalse();
    expect($review->images)->toHaveCount(1);
    Storage::disk('public')->assertExists($review->images[0]);
});

it('shows the product details and saved reviews without exposing reviewer email', function () {
    $unsafeDescription = '<script>alert("details")</script>';
    $unsafeReviewerName = '<script>alert("name")</script>';
    $unsafeReviewBody = '<script>alert("review")</script>';
    $product = createReviewTestProduct([
        'description' => "Sourced from local farms.\nPacked with care.\n{$unsafeDescription}",
    ]);
    $product->reviews()->create([
        'name' => $unsafeReviewerName,
        'email' => 'private@example.com',
        'rating' => 4,
        'body' => $unsafeReviewBody,
        'images' => [],
        'is_approved' => true,
    ]);
    $product->reviews()->create([
        'name' => 'Karim Hasan',
        'email' => 'karim@example.com',
        'rating' => 5,
        'body' => 'Would order again.',
        'images' => [],
    ]);

    $response = $this->get(route('products.show', ['product' => $product->slug]));

    $response->assertSee('Product Details');
    $response->assertSee('Sourced from local farms.');
    $response->assertSee('Packed with care.');
    $response->assertSee('4.0');
    $response->assertSee('1 review');
    $response->assertSee($unsafeDescription);
    $response->assertSee($unsafeReviewerName);
    $response->assertSee($unsafeReviewBody);
    $response->assertDontSee($unsafeDescription, false);
    $response->assertDontSee($unsafeReviewerName, false);
    $response->assertDontSee($unsafeReviewBody, false);
    $response->assertDontSee('Would order again.');
    $response->assertDontSee('Karim Hasan');
    $response->assertSee('Write a review');
    $response->assertSee('name="images[]"', false);
    $response->assertDontSee('private@example.com');
});

it('requires review details before submission', function () {
    $product = createReviewTestProduct();

    $response = $this->from(route('products.show', ['product' => $product->slug]))
        ->post(route('products.reviews.store', ['product' => $product->slug]), []);

    $response->assertSessionHasErrors(['name', 'email', 'rating', 'body']);
    $this->assertDatabaseCount('product_reviews', 0);
});

it('rejects ratings outside the allowed range and more than three images', function () {
    Storage::fake('public');
    $product = createReviewTestProduct();

    $response = $this->post(route('products.reviews.store', ['product' => $product->slug]), [
        'name' => 'Amina Rahman',
        'email' => 'amina@example.com',
        'rating' => 6,
        'body' => 'Fresh and excellent quality.',
        'images' => [
            UploadedFile::fake()->image('one.jpg'),
            UploadedFile::fake()->image('two.jpg'),
            UploadedFile::fake()->image('three.jpg'),
            UploadedFile::fake()->image('four.jpg'),
        ],
    ]);

    $response->assertSessionHasErrors(['rating', 'images']);
    $this->assertDatabaseCount('product_reviews', 0);
    Storage::disk('public')->assertDirectoryEmpty('product-reviews');
});

it('rejects files that are not images', function () {
    Storage::fake('public');
    $product = createReviewTestProduct();

    $response = $this->post(route('products.reviews.store', ['product' => $product->slug]), [
        'name' => 'Amina Rahman',
        'email' => 'amina@example.com',
        'rating' => 5,
        'body' => 'Fresh and excellent quality.',
        'images' => [UploadedFile::fake()->create('notes.txt', 1, 'text/plain')],
    ]);

    $response->assertSessionHasErrors('images.0');
    $this->assertDatabaseCount('product_reviews', 0);
    Storage::disk('public')->assertDirectoryEmpty('product-reviews');
});

function createReviewTestProduct(array $attributes = []): Product
{
    $category = Category::create([
        'name' => 'Review Products',
        'slug' => 'review-products',
        'is_active' => true,
    ]);

    return Product::create(array_merge([
        'category_id' => $category->id,
        'name' => 'Review Product',
        'slug' => 'review-product',
        'sku' => 'REVIEW-PRODUCT-001',
        'short_description' => 'A product for review testing.',
        'price' => 120.00,
        'stock' => 10,
        'type' => 'simple',
        'is_active' => true,
    ], $attributes));
}
