<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StorefrontDemoSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $categories = [
      [
        'name' => 'Fresh Fruits',
        'products' => [
          'Mango',
          'Apple',
          'Banana',
          'Orange',
          'Grapes',
          'Pineapple',
          'Watermelon',
          'Papaya',
          'Guava',
          'Lychee',
          'Dragon Fruit',
          'Pear',
          'Plum',
          'Peach',
          'Strawberry',
        ],
      ],
      [
        'name' => 'Vegetables',
        'products' => [
          'Carrot',
          'Tomato',
          'Potato',
          'Cabbage',
          'Spinach',
          'Cauliflower',
          'Cucumber',
          'Pumpkin',
          'Broccoli',
          'Onion',
          'Garlic',
          'Lettuce',
          'Eggplant',
          'Green Bean',
          'Radish',
        ],
      ],
      [
        'name' => 'Grocery Staples',
        'products' => [
          'Rice',
          'Flour',
          'Sugar',
          'Salt',
          'Oil',
          'Pulse',
          'Tea',
          'Coconut Oil',
          'Spices',
          'Basmati Rice',
          'Chickpeas',
          'Lentils',
          'Soy Sauce',
          'Mustard Oil',
          'Jaggery',
        ],
      ],
      [
        'name' => 'Dairy & Bakery',
        'products' => [
          'Milk',
          'Yogurt',
          'Cheese',
          'Butter',
          'Eggs',
          'Bread',
          'Bun',
          'Croissant',
          'Paneer',
          'Cream',
          'Sourdough',
          'Bagel',
          'Muffin',
          'Cookies',
          'Pastry',
        ],
      ],
    ];

    foreach ($categories as $categoryIndex => $categoryData) {
      $category = Category::updateOrCreate(
        ['slug' => Str::slug($categoryData['name'])],
        [
          'name' => $categoryData['name'],
          'description' => 'Demo products for ' . $categoryData['name'],
          'is_active' => true,
          'sort_order' => $categoryIndex + 1,
        ]
      );

      foreach ($categoryData['products'] as $productIndex => $productName) {
        $slug = Str::slug($productName . '-' . $category->slug . '-' . ($productIndex + 1));

        Product::updateOrCreate(
          ['slug' => $slug],
          [
            'category_id' => $category->id,
            'name' => $productName,
            'sku' => strtoupper(Str::slug($productName . '-' . $category->slug . '-' . ($productIndex + 1))) . '-' . ($productIndex + 1),
            'short_description' => 'Fresh ' . $productName . ' for your daily needs.',
            'description' => 'High-quality ' . $productName . ' selected for freshness, taste, and everyday convenience.',
            'price' => 80 + ($productIndex * 25) + ($categoryIndex * 15),
            'compare_at_price' => 110 + ($productIndex * 30) + ($categoryIndex * 20),
            'stock' => 25 + $productIndex,
            'type' => 'simple',
            'is_active' => true,
          ]
        );
      }

      $existingProductCount = Product::where('category_id', $category->id)->count();
      if ($existingProductCount < 15) {
        for ($extraIndex = $existingProductCount; $extraIndex < 15; $extraIndex++) {
          $extraName = $categoryData['name'] . ' Item ' . ($extraIndex + 1);
          $extraSlug = Str::slug($extraName . '-' . $category->slug . '-' . ($extraIndex + 1));

          Product::updateOrCreate(
            ['slug' => $extraSlug],
            [
              'category_id' => $category->id,
              'name' => $extraName,
              'sku' => strtoupper(Str::slug($extraName . '-' . $category->slug . '-' . ($extraIndex + 1))) . '-' . ($extraIndex + 1),
              'short_description' => 'Fresh ' . $extraName . ' for your daily needs.',
              'description' => 'Premium quality ' . $extraName . ' with fresh taste and reliable value.',
              'price' => 120 + ($extraIndex * 18) + ($categoryIndex * 12),
              'compare_at_price' => 150 + ($extraIndex * 22) + ($categoryIndex * 14),
              'stock' => 30 + $extraIndex,
              'type' => 'simple',
              'is_active' => true,
            ]
          );
        }
      }
    }
  }
}
