<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Database\Seeder;

class ProductReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $product = Product::query()
            ->where('is_active', true)
            ->first();

        if (! $product || $product->reviews()->exists()) {
            return;
        }

        ProductReview::factory()
            ->count(3)
            ->create(['product_id' => $product->id]);
    }
}
