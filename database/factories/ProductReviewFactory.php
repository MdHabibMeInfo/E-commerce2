<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductReview>
 */
class ProductReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => fn (): int => Product::query()->value('id')
                ?? throw new \LogicException('Create a product before generating a review.'),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'rating' => fake()->numberBetween(1, 5),
            'body' => fake()->paragraph(),
            'images' => [],
        ];
    }
}
