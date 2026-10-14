<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('stores a customer review and its uploaded photo', function () {
    Storage::fake('public');
    $product = createReviewTestProduct();
    $user = User::factory()->create([
        'name' => 'Amina Rahman',
        'email' => 'amina@example.com',
    ]);
    $image = UploadedFile::fake()->image('review.jpg');

    $response = $this->actingAs($user)->post(route('products.reviews.store', ['product' => $product->slug]), [
        'name' => 'Forged Reviewer',
        'email' => 'forged@example.com',
        'rating' => 5,
        'body' => 'Fresh and excellent quality.',
        'images' => [$image],
        'is_approved' => true,
    ]);

    $response->assertRedirect(route('products.show', ['product' => $product->slug]) . '#customer-reviews');
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

it('shows signed-in reviewers a form without name or email inputs', function () {
    $product = createReviewTestProduct();
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('products.show', ['product' => $product->slug]));

    $response->assertSee('name="rating"', false);
    $response->assertSee('name="body"', false);
    $response->assertDontSee('name="name"', false);
    $response->assertDontSee('name="email"', false);
});

it('shows the product details and saved reviews without exposing reviewer email', function () {
    $unsafeDescription = '<script>alert("details")</script>';
    $unsafeReviewerName = '<script>alert("name")</script>';
    $unsafeReviewBody = '<script>alert("review")</script>';
    $product = createReviewTestProduct([
        'description' => "Sourced from local farms.\nPacked with care.\n{$unsafeDescription}",
    ]);
    Product::create([
        'category_id' => $product->category_id,
        'name' => 'Related Review Test Product',
        'slug' => 'related-review-test-product',
        'type' => 'simple',
        'price' => 100,
        'is_active' => true,
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
    $response->assertSee('Customer Reviews');
    $response->assertDontSee('Product information');
    $response->assertDontSee('Customer feedback');
    $response->assertSee('Related Products');
    $response->assertSee('Related Review Test Product');
    $response->assertSee('data-related-product-track', false);
    $response->assertSeeInOrder(['id="customer-reviews"', 'id="related-products"', '<footer'], false);
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
    $response->assertSee(route('login'));
    $response->assertSee(route('register'));
    $response->assertDontSee('name="name"', false);
    $response->assertDontSee('name="email"', false);
    $response->assertDontSee('<time', false);
    $response->assertDontSee('private@example.com');
});

it('redirects guests to sign in before accepting a review', function () {
    $product = createReviewTestProduct();

    $response = $this->from(route('products.show', ['product' => $product->slug]))
        ->post(route('products.reviews.store', ['product' => $product->slug]), [
            'rating' => 5,
            'body' => 'Fresh and excellent quality.',
        ]);

    $response->assertRedirect(route('login'));
    $this->assertDatabaseCount('product_reviews', 0);
});

it('requires a rating and review body from signed-in customers', function () {
    $product = createReviewTestProduct();
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('products.show', ['product' => $product->slug]))
        ->post(route('products.reviews.store', ['product' => $product->slug]), []);

    $response->assertSessionHasErrors(['rating', 'body']);
    $this->assertDatabaseCount('product_reviews', 0);
});

it('rejects ratings outside the allowed range and more than three images', function () {
    Storage::fake('public');
    $product = createReviewTestProduct();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('products.reviews.store', ['product' => $product->slug]), [
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
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('products.reviews.store', ['product' => $product->slug]), [
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
