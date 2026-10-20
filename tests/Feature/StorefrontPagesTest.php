<?php

use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CouponService;
use App\Services\ProductVariantService;
use App\Services\SiteSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('loads the home page', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

it('renders the title supplied by each storefront page', function () {
    $this->get(route('about'))
        ->assertSee('<title>About Us | ' . config('app.name') . '</title>', false);

    $this->get(route('home'))
        ->assertSee('<title>' . config('app.name') . ' — Fresh &amp; Quality Food &amp; Grocery Products</title>', false);
});

it('highlights the active storefront page in the navbar', function () {
    $this->get(route('shop'))
        ->assertSee('href="' . route('shop') . '"', false)
        ->assertSee('aria-label="Shop"', false)
        ->assertSee('aria-current="page"', false);
});

it('shows the saved About Us article and WhatsApp contact in the storefront', function () {
    $settingsUser = User::factory()->create();
    app(SiteSettings::class)->update([
        'about_us_article' => 'Quality food, delivered with care.',
        'contact_page_content' => 'Our team is available to help with your order.',
        'whatsapp_number' => '+880 1712-345678',
        'whatsapp_url' => null,
    ], $settingsUser->id);

    $this->get(route('about'))
        ->assertOk()
        ->assertSee('Quality food, delivered with care.');

    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('Our team is available to help with your order.')
        ->assertSee('href="tel:+8801712345678"', false)
        ->assertSee('href="https://wa.me/8801712345678"', false);

    $this->get(route('home'))
        ->assertSee('href="' . route('about') . '"', false)
        ->assertSee('href="' . route('contact') . '"', false)
        ->assertSee('href="' . route('shop') . '"', false)
        ->assertSee('aria-label="Shop"', false)
        ->assertSee('href="tel:+8801712345678"', false)
        ->assertSee('href="https://wa.me/8801712345678"', false)
        ->assertSee('Call Us')
        ->assertDontSee('aria-label="Mobile navigation"', false)
        ->assertDontSee('Wishlist')
        ->assertSee('+880 1712-345678');
});

it('shows owner-managed policy pages with escaped text and working navigation links', function () {
    $settingsUser = User::factory()->create();
    app(SiteSettings::class)->update([
        'terms_conditions' => 'Orders are subject to these terms.',
        'privacy_policy' => "<script>alert('xss')</script>\nCustomer data is used to fulfill orders.",
        'return_refund_policy' => 'Report damaged items within 3 days.',
    ], $settingsUser->id);

    $this->get(route('terms'))
        ->assertOk()
        ->assertSee('Terms &amp; Conditions', false)
        ->assertSee('শর্তাবলী')
        ->assertSee('Orders are subject to these terms.');

    $this->get(route('privacy'))
        ->assertOk()
        ->assertSee('Privacy Policy')
        ->assertSee('গোপনীয়তা নীতি')
        ->assertSee('&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;', false)
        ->assertDontSee("<script>alert('xss')</script>", false);

    $this->get(route('returns'))
        ->assertOk()
        ->assertSee('Return / Refund Policy')
        ->assertSee('রিটার্ন ও রিফান্ড পলিসি')
        ->assertSee('Report damaged items within 3 days.');

    $this->get(route('home'))
        ->assertSee('href="' . route('terms') . '"', false)
        ->assertSee('href="' . route('privacy') . '"', false)
        ->assertSee('href="' . route('returns') . '"', false);
});

it('does not publish hard-coded About Us copy when the owner has not provided content', function () {
    $this->get(route('about'))
        ->assertOk()
        ->assertSee('This page has not been published yet.')
        ->assertDontSee("Bangladesh's trusted home for pure, organic, and wholesome food.");
});

it('opens profile and logout options from the signed-in profile icon', function () {
    $user = User::factory()->create([
        'name' => 'Storefront Shopper',
    ]);

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertSee('href="' . route('account.profile') . '"', false)
        ->assertSee('aria-label="Open profile and logout menu"', false)
        ->assertSee('aria-label="Avatar for Storefront Shopper"', false)
        ->assertSee('data-account-menu-toggle', false)
        ->assertSee('aria-label="Account menu"', false)
        ->assertSee('class="hidden border-b border-[#dedfcf]', false)
        ->assertSee('href="' . route('account.orders.history') . '"', false)
        ->assertSee('href="' . route('account.security') . '"', false)
        ->assertSee('action="' . route('logout') . '"', false)
        ->assertSee('>Profile</span>', false)
        ->assertSee('>Logout</span>', false)
        ->assertDontSee('My Orders');
});

it('shows five home category rows by skipping categories without active products', function () {
    for ($position = 1; $position <= 8; $position++) {
        $category = Category::create([
            'name' => 'Homepage Category ' . $position,
            'slug' => 'homepage-category-' . $position,
            'is_active' => true,
            'sort_order' => $position,
        ]);

        if ($position === 2) {
            Product::create([
                'category_id' => $category->id,
                'name' => 'Inactive Homepage Product',
                'slug' => 'inactive-homepage-product',
                'type' => 'simple',
                'is_active' => false,
            ]);
        }

        if ($position >= 3) {
            Product::create([
                'category_id' => $category->id,
                'name' => 'Homepage Product ' . $position,
                'slug' => 'homepage-product-' . $position,
                'type' => 'simple',
                'is_active' => true,
            ]);
        }
    }

    $response = $this->get(route('home'));

    foreach (range(3, 7) as $position) {
        $response->assertSeeInOrder([
            'data-category-product-track="homepage-category-' . $position . '"',
            'data-product-card',
            'Homepage Product ' . $position,
        ], false);
    }

    $response->assertDontSee('data-category-product-track="homepage-category-1"', false);
    $response->assertDontSee('data-category-product-track="homepage-category-2"', false);
    $response->assertDontSee('data-category-product-track="homepage-category-8"', false);
});

it('shows new arrivals and selected products in Just For You with a non-empty fallback', function () {
    $selectedProduct = Product::create([
        'name' => 'Selected Homepage Product',
        'slug' => 'selected-homepage-product',
        'price' => 120,
        'type' => 'simple',
        'is_active' => true,
        'is_featured' => true,
        'created_at' => now()->subDay(),
    ]);
    $newArrival = Product::create([
        'name' => 'Newest Homepage Product',
        'slug' => 'newest-homepage-product',
        'price' => 140,
        'type' => 'simple',
        'is_active' => true,
        'created_at' => now(),
    ]);

    $response = $this->get(route('home'));

    $response
        ->assertSee('id="new-arrivals"', false)
        ->assertSee('New Arrivals')
        ->assertSee('id="just-for-you"', false)
        ->assertSee('Just For You')
        ->assertViewHas('newArrivals', fn($products) => $products->first()?->is($newArrival))
        ->assertViewHas('justForYouProducts', fn($products) => $products->contains(fn($product) => $product->is($selectedProduct))
            && ! $products->contains(fn($product) => $product->is($newArrival)));

    $selectedProduct->forceFill(['is_featured' => false])->save();

    $this->get(route('home'))
        ->assertViewHas('justForYouProducts', fn($products) => $products->first()?->is($newArrival));
});

it('shows the active variant price range on home and shop product cards', function () {
    $product = Product::create([
        'name' => 'Price Range Shirt',
        'slug' => 'price-range-shirt',
        'type' => 'variable',
        'is_active' => true,
    ]);
    $size = ProductAttribute::create([
        'name' => 'Size',
        'slug' => 'price-range-size',
        'is_active' => true,
    ]);
    $product->productAttributes()->attach($size);
    $small = $size->values()->create([
        'value' => 'Small',
        'slug' => 'price-range-small',
        'is_active' => true,
    ]);
    $large = $size->values()->create([
        'value' => 'Large',
        'slug' => 'price-range-large',
        'is_active' => true,
    ]);
    $inactive = $size->values()->create([
        'value' => 'Inactive',
        'slug' => 'price-range-inactive',
        'is_active' => true,
    ]);
    $variantService = app(ProductVariantService::class);
    $variantService->createVariant($product, [
        'sku' => 'PRICE-RANGE-SHIRT-SMALL',
        'attribute_value_ids' => [$small->id],
        'price' => 245,
    ]);
    $variantService->createVariant($product, [
        'sku' => 'PRICE-RANGE-SHIRT-LARGE',
        'attribute_value_ids' => [$large->id],
        'price' => 395,
    ]);
    $variantService->createVariant($product, [
        'sku' => 'PRICE-RANGE-SHIRT-INACTIVE',
        'attribute_value_ids' => [$inactive->id],
        'price' => 950,
        'is_active' => false,
    ]);

    $this->get(route('home'))
        ->assertSee('৳245.00 – ৳395.00')
        ->assertDontSee('৳950.00');

    $this->get(route('shop'))
        ->assertSee('৳245.00 – ৳395.00')
        ->assertDontSee('৳950.00');
});

it('shows only approved customer reviews before the footer', function () {
    $product = Product::create([
        'name' => 'Review Homepage Product',
        'slug' => 'review-homepage-product',
        'price' => 180,
        'type' => 'simple',
        'is_active' => true,
    ]);
    $product->reviews()->create([
        'name' => 'Approved Customer',
        'email' => 'approved@example.com',
        'rating' => 5,
        'body' => 'Approved review for the homepage.',
        'is_approved' => true,
    ]);
    User::factory()->create([
        'email' => 'approved@example.com',
        'profile_photo_path' => 'profile-photos/approved-customer.jpg',
    ]);
    $product->reviews()->create([
        'name' => 'Avatar Customer',
        'email' => 'avatar@example.com',
        'rating' => 4,
        'body' => 'A full review body that should remain visible without truncation.',
        'is_approved' => true,
    ]);
    $product->reviews()->create([
        'name' => 'Waiting Customer',
        'email' => 'waiting@example.com',
        'rating' => 1,
        'body' => 'This review is still waiting.',
    ]);

    $this->get(route('home'))
        ->assertSeeInOrder(['id="just-for-you"', 'id="customer-reviews"', '<footer'], false)
        ->assertDontSee('Customer feedback')
        ->assertDontSee('Customer Reviews')
        ->assertSee('Approved Customer')
        ->assertSee('Approved review for the homepage.')
        ->assertSee('Approved Customer profile photo')
        ->assertSee('role="img" aria-label="Avatar for Avatar Customer"', false)
        ->assertSee('A full review body that should remain visible without truncation.')
        ->assertSee('Review for')
        ->assertSee('Review Homepage Product')
        ->assertSee('data-home-review-track', false)
        ->assertSee('data-home-review-pagination', false)
        ->assertSee('data-carousel-item', false)
        ->assertDontSee('<time', false)
        ->assertDontSee('Waiting Customer')
        ->assertDontSee('This review is still waiting.');
});

it('paginates shop products and preserves search and sort on category links', function () {
    $category = Category::create([
        'name' => 'Pagination Category',
        'slug' => 'pagination-category',
        'is_active' => true,
    ]);

    for ($position = 1; $position <= 17; $position++) {
        Product::create([
            'category_id' => $category->id,
            'name' => 'Pagination Product ' . str_pad((string) $position, 2, '0', STR_PAD_LEFT),
            'slug' => 'pagination-product-' . $position,
            'type' => 'simple',
            'is_active' => true,
        ]);
    }

    $response = $this->get(route('shop', [
        'q' => 'Pagination',
        'sort' => 'oldest',
        'per_page' => '16',
    ]));

    $response
        ->assertSee('name="per_page"', false)
        ->assertSee('aria-checked="true" data-shop-option="oldest"', false)
        ->assertSee('aria-checked="true" data-shop-option="16"', false)
        ->assertSee('page=2', false)
        ->assertSee('q=Pagination', false)
        ->assertSee('/shop/pagination-category?sort=oldest&amp;per_page=16&amp;q=Pagination', false);

    $this->get(route('shop', [
        'q' => 'Pagination',
        'sort' => 'oldest',
        'per_page' => '16',
        'page' => 2,
    ]))->assertSee('Pagination Product 17');
});

it('shows all products after leaving a category with a lower maximum price', function () {
    $lowPriceCategory = Category::create([
        'name' => 'Low Price Category',
        'slug' => 'low-price-category',
        'is_active' => true,
    ]);
    $highPriceCategory = Category::create([
        'name' => 'High Price Category',
        'slug' => 'high-price-category',
        'is_active' => true,
    ]);
    Product::create([
        'category_id' => $lowPriceCategory->id,
        'name' => 'Low Price Product',
        'slug' => 'low-price-product',
        'price' => 80,
        'type' => 'simple',
        'is_active' => true,
    ]);
    Product::create([
        'category_id' => $highPriceCategory->id,
        'name' => 'High Price Product',
        'slug' => 'high-price-product',
        'price' => 500,
        'type' => 'simple',
        'is_active' => true,
    ]);

    $categoryResponse = $this->get(route('shop', [
        'category' => $lowPriceCategory->slug,
        'sort' => 'price_asc',
        'max_price' => 80,
    ]));

    $categoryResponse
        ->assertOk()
        ->assertSee('href="' . route('shop', ['sort' => 'price_asc']) . '"', false)
        ->assertDontSee('<input type="hidden" name="max_price" value="80">', false);

    $this->get(route('shop'))
        ->assertSee('Low Price Product')
        ->assertSee('High Price Product');
});

it('returns up to eight active products for storefront search suggestions', function () {
    $category = Category::create([
        'name' => 'Fresh Produce',
        'slug' => 'fresh-produce',
        'is_active' => true,
    ]);

    for ($position = 1; $position <= 10; $position++) {
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Fresh Apple ' . $position,
            'slug' => 'fresh-apple-' . $position,
            'price' => 240,
            'type' => 'simple',
            'is_active' => true,
        ]);

        if ($position === 1) {
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => 'products/fresh-apple.jpg',
                'alt_text' => 'Fresh apple',
                'is_primary' => true,
                'sort_order' => 0,
            ]);
        }

        if ($position === 2) {
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => 'products/fresh-apple-secondary.jpg',
                'alt_text' => 'Fresh apple packaging',
                'is_primary' => false,
                'sort_order' => 1,
            ]);
        }
    }

    Product::create([
        'category_id' => $category->id,
        'name' => 'Fresh Inactive Product',
        'slug' => 'fresh-inactive-product',
        'type' => 'simple',
        'is_active' => false,
    ]);

    $this->getJson(route('shop.suggestions', ['search' => 'Fresh']))
        ->assertOk()
        ->assertJsonCount(8)
        ->assertJsonStructure([['name', 'category', 'price', 'image', 'url']])
        ->assertJsonFragment([
            'name' => 'Fresh Apple 1',
            'price' => '240.00',
            'image' => '/storage/products/fresh-apple.jpg',
        ])
        ->assertJsonFragment([
            'name' => 'Fresh Apple 10',
            'image' => '/storage/products/fresh-apple-secondary.jpg',
        ])
        ->assertJsonMissing(['name' => 'Fresh Inactive Product']);

    $this->getJson(route('shop.suggestions', ['search' => 'F']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('search');
});

it('lists out-of-stock products last and disables their cart button', function () {
    Product::create([
        'name' => 'Available Product First',
        'slug' => 'available-product-first',
        'price' => 240,
        'type' => 'simple',
        'is_active' => true,
    ]);
    $outOfStockProduct = Product::create([
        'name' => 'Unavailable Product Last',
        'slug' => 'unavailable-product-last',
        'price' => 10,
        'type' => 'simple',
        'is_active' => true,
    ]);
    $outOfStockProduct->forceFill(['is_out_of_stock' => true])->save();

    $this->get(route('shop', ['sort' => 'price_asc']))
        ->assertOk()
        ->assertSeeInOrder(['Available Product First', 'Unavailable Product Last'])
        ->assertSee('Out of stock')
        ->assertSee('disabled', false);

    $this->get(route('products.show', 'unavailable-product-last'))
        ->assertOk()
        ->assertSee('Out of stock')
        ->assertSee('disabled', false)
        ->assertDontSee('data-buy-now', false);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Unavailable Product Last')
        ->assertSee('Out of stock');
});

it('shows product names and current and compare-at prices on home and shop cards', function () {
    $category = Category::create([
        'name' => 'Fresh Fruits',
        'slug' => 'fresh-fruits',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    Product::create([
        'category_id' => $category->id,
        'name' => 'Fresh Mango',
        'slug' => 'fresh-mango',
        'sku' => 'MANGO-CARD-001',
        'short_description' => 'Sweet and juicy mango.',
        'price' => 240.00,
        'compare_at_price' => 299.00,
        'stock' => 20,
        'type' => 'simple',
        'is_active' => true,
    ]);

    foreach ([route('home'), route('shop')] as $url) {
        $response = $this->get($url);

        $response->assertSee('Fresh Mango');
        $response->assertSee('240.00');
        $response->assertSee('299.00');
        $response->assertDontSee('Sweet and juicy mango.');
    }
});

it('loads the shop page with products', function () {
    $category = Category::create([
        'name' => 'Fresh Fruits',
        'slug' => 'fresh-fruits',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    Product::create([
        'category_id' => $category->id,
        'name' => 'Fresh Mango',
        'slug' => 'fresh-mango',
        'sku' => 'MANGO-001',
        'short_description' => 'Sweet and juicy mango.',
        'price' => 240.00,
        'compare_at_price' => 299.00,
        'stock' => 20,
        'type' => 'simple',
        'is_active' => true,
    ]);

    $response = $this->get(route('shop'));

    $response->assertOk();
    $response->assertSee('Fresh Mango');
    $response->assertSee('data-open-cart');
    $response->assertSee('data-product-card');
    $response->assertSee('data-product-name');
    $response->assertSee('Fresh Mango');
    $response->assertSee('Default Sorting');
    $response->assertSee('Sort by Latest');
    $response->assertSee('Sort by Oldest');
    $response->assertSee('Price Low to High');
    $response->assertSee('Price High to Low');

    $categoryUrl = route('shop', ['category' => $category->slug]);
    $categoryResponse = $this->get(route('shop', ['category' => $category->slug, 'sort' => 'latest']))
        ->assertOk();

    expect(preg_match(
        '/<link\s+rel="canonical"\s+href="' . preg_quote($categoryUrl, '/') . '"\s*>/',
        $categoryResponse->getContent(),
    ))->toBe(1);
});

it('sorts shop products according to the selected order', function (?string $sort, array $expectedNames) {
    $category = Category::create([
        'name' => 'Fresh Fruits',
        'slug' => 'fresh-fruits',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    foreach (
        [
            ['name' => 'Old Product', 'sku' => 'SORT-001', 'price' => 200.00],
            ['name' => 'Middle Product', 'sku' => 'SORT-002', 'price' => 500.00],
            ['name' => 'New Product', 'sku' => 'SORT-003', 'price' => 100.00],
        ] as $productData
    ) {
        Product::create([
            'category_id' => $category->id,
            'name' => $productData['name'],
            'slug' => str($productData['name'])->slug(),
            'sku' => $productData['sku'],
            'price' => $productData['price'],
            'stock' => 20,
            'type' => 'simple',
            'is_active' => true,
        ]);
    }

    $url = $sort === null ? route('shop') : route('shop', ['sort' => $sort]);
    $response = $this->get($url);

    $response->assertSeeTextInOrder($expectedNames);
})->with([
    'default sorting' => [null, ['New Product', 'Middle Product', 'Old Product']],
    'latest first' => ['latest', ['New Product', 'Middle Product', 'Old Product']],
    'oldest first' => ['oldest', ['Old Product', 'Middle Product', 'New Product']],
    'price low to high' => ['price_asc', ['New Product', 'Old Product', 'Middle Product']],
    'price high to low' => ['price_desc', ['Middle Product', 'Old Product', 'New Product']],
]);

it('sorts variable products by their active variant prices', function (string $sort, array $expectedNames) {
    $category = Category::create([
        'name' => 'Variable Price Sorting',
        'slug' => 'variable-price-sorting',
        'is_active' => true,
    ]);
    $variableProduct = Product::create([
        'category_id' => $category->id,
        'name' => 'Variable Price Product',
        'slug' => 'variable-price-product',
        'type' => 'variable',
        'is_active' => true,
    ]);
    ProductVariant::create([
        'product_id' => $variableProduct->id,
        'name' => 'Active variant',
        'sku' => 'VARIABLE-SORT-ACTIVE',
        'combination_hash' => hash('sha256', 'variable-sort-active'),
        'price' => 500,
        'is_active' => true,
    ]);
    ProductVariant::create([
        'product_id' => $variableProduct->id,
        'name' => 'Inactive low variant',
        'sku' => 'VARIABLE-SORT-INACTIVE',
        'combination_hash' => hash('sha256', 'variable-sort-inactive'),
        'price' => 10,
        'is_active' => false,
    ]);
    Product::create([
        'category_id' => $category->id,
        'name' => 'Simple Price Product',
        'slug' => 'simple-price-product',
        'price' => 250,
        'type' => 'simple',
        'is_active' => true,
    ]);

    $this->get(route('shop', ['sort' => $sort]))
        ->assertSeeTextInOrder($expectedNames);
})->with([
    'lowest first' => ['price_asc', ['Simple Price Product', 'Variable Price Product']],
    'highest first' => ['price_desc', ['Variable Price Product', 'Simple Price Product']],
]);

it('falls back safely when shop query filters have malformed values', function () {
    $this->get(route('shop', [
        'q' => ['unexpected'],
        'sort' => ['unsupported'],
        'per_page' => ['500'],
        'flag' => ['unsupported'],
        'min_price' => ['not-a-price'],
        'max_price' => ['not-a-price'],
    ]))
        ->assertOk()
        ->assertSee('Default Sorting')
        ->assertSee('Default');
});

it('shows the shop-by category, price, and product-variant filters on the shop page', function () {
    $category = Category::create([
        'name' => 'Fresh Fruits',
        'slug' => 'fresh-fruits',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    Product::create([
        'category_id' => $category->id,
        'name' => 'Fresh Mango',
        'slug' => 'fresh-mango',
        'sku' => 'MANGO-002',
        'short_description' => 'Sweet and juicy mango.',
        'price' => 240.00,
        'compare_at_price' => 299.00,
        'stock' => 20,
        'type' => 'simple',
        'is_active' => true,
    ]);

    $response = $this->get(route('shop'));

    $response->assertOk();
    $response->assertSee('Category');
    $response->assertSee('Price Range');
    $response->assertSee('data-price-filter-apply hidden', false);
    $response->assertSee('Apply price filter');
    $response->assertSee('Product Variants');
    $response->assertDontSee('Brands');
    $response->assertDontSee('Apply Filter');
    $response->assertDontSee('Reset');
});

it('limits variant filter options to products in the selected category', function () {
    $freshFruits = Category::create([
        'name' => 'Fresh Fruits',
        'slug' => 'variant-filter-fruits',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $vegetables = Category::create([
        'name' => 'Vegetables',
        'slug' => 'variant-filter-vegetables',
        'is_active' => true,
        'sort_order' => 2,
    ]);
    $mango = Product::create([
        'category_id' => $freshFruits->id,
        'name' => 'Variant Filter Mango',
        'slug' => 'variant-filter-mango',
        'type' => 'variable',
        'is_active' => true,
    ]);
    $potato = Product::create([
        'category_id' => $vegetables->id,
        'name' => 'Variant Filter Potato',
        'slug' => 'variant-filter-potato',
        'type' => 'variable',
        'is_active' => true,
    ]);
    ProductVariant::create([
        'product_id' => $mango->id,
        'name' => 'Mango Size Option',
        'sku' => 'VARIANT-FILTER-MANGO',
        'combination_hash' => hash('sha256', 'variant-filter-mango'),
        'price' => 100,
        'is_active' => true,
    ]);
    ProductVariant::create([
        'product_id' => $potato->id,
        'name' => 'Potato Size Option',
        'sku' => 'VARIANT-FILTER-POTATO',
        'combination_hash' => hash('sha256', 'variant-filter-potato'),
        'price' => 80,
        'is_active' => true,
    ]);

    $this->get(route('shop', ['category' => $freshFruits->slug]))
        ->assertSee('Mango Size Option')
        ->assertDontSee('Potato Size Option');
});

it('keeps variable products visible when the full default price range is submitted', function () {
    $televisions = Category::create([
        'name' => 'TV',
        'slug' => 'variable-tv-category',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    $otherCategory = Category::create([
        'name' => 'Other Products',
        'slug' => 'variable-tv-other',
        'is_active' => true,
        'sort_order' => 2,
    ]);
    $television = Product::create([
        'category_id' => $televisions->id,
        'name' => 'Variant Television',
        'slug' => 'variant-television',
        'type' => 'variable',
        'is_active' => true,
    ]);
    Product::create([
        'category_id' => $otherCategory->id,
        'name' => 'High Priced Simple Product',
        'slug' => 'high-priced-simple-product',
        'type' => 'simple',
        'price' => 300050,
        'is_active' => true,
    ]);
    ProductVariant::create([
        'product_id' => $television->id,
        'name' => 'Television Size Option',
        'sku' => 'VARIABLE-TV-OPTION',
        'combination_hash' => hash('sha256', 'variable-tv-option'),
        'price' => 22000,
        'is_active' => true,
        'is_out_of_stock' => false,
    ]);

    $this->get(route('shop', ['category' => $televisions->slug]))
        ->assertSee('Variant Television')
        ->assertSee('max="22000"', false);

    $this->get(route('shop', [
        'category' => $televisions->slug,
        'min_price' => 0,
        'max_price' => 300050,
    ]))
        ->assertSee('Variant Television')
        ->assertSee('max="22000"', false);

    $this->get(route('shop', [
        'category' => $televisions->slug,
        'min_price' => 21000,
        'max_price' => 22000,
    ]))
        ->assertSee('Variant Television')
        ->assertSee('max="22000"', false);
});

it('applies a min-price filter without forcing an impossible max range', function () {
    $category = Category::create([
        'name' => 'Fresh Fruits',
        'slug' => 'fresh-fruits',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    Product::create([
        'category_id' => $category->id,
        'name' => 'Fresh Mango',
        'slug' => 'fresh-mango',
        'sku' => 'MANGO-004',
        'short_description' => 'Sweet and juicy mango.',
        'price' => 240.00,
        'compare_at_price' => 299.00,
        'stock' => 20,
        'type' => 'simple',
        'is_active' => true,
    ]);

    $response = $this->get(route('shop', ['min_price' => 200]));

    $response->assertOk();
    $response->assertSee('Fresh Mango');
});

it('accepts manually entered prices that are not multiples of ten', function () {
    $category = Category::create([
        'name' => 'Exact Price Filter',
        'slug' => 'exact-price-filter',
        'is_active' => true,
    ]);
    Product::create([
        'category_id' => $category->id,
        'name' => 'Exact 425 Product',
        'slug' => 'exact-425-product',
        'price' => 425,
        'type' => 'simple',
        'is_active' => true,
    ]);
    Product::create([
        'category_id' => $category->id,
        'name' => 'Above 425 Product',
        'slug' => 'above-425-product',
        'price' => 450,
        'type' => 'simple',
        'is_active' => true,
    ]);

    $this->get(route('shop', [
        'category' => $category->slug,
        'min_price' => 425,
        'max_price' => 425,
    ]))
        ->assertOk()
        ->assertSee('step="0.01"', false)
        ->assertSee('Exact 425 Product')
        ->assertDontSee('Above 425 Product');
});

it('stores a product in the database cart', function () {
    $category = Category::create([
        'name' => 'Fresh Fruits',
        'slug' => 'fresh-fruits',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Fresh Mango',
        'slug' => 'fresh-mango',
        'sku' => 'MANGO-003',
        'short_description' => 'Sweet and juicy mango.',
        'price' => 240.00,
        'compare_at_price' => 299.00,
        'stock' => 20,
        'type' => 'simple',
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/cart/items', [
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('carts', ['session_id' => session()->getId()]);
    $this->assertDatabaseHas('cart_items', [
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
});

it('returns active variant promotion savings for the matching cart line only', function () {
    $product = Product::create([
        'name' => 'Promoted variable product',
        'slug' => 'promoted-variable-product',
        'type' => 'variable',
        'is_active' => true,
    ]);
    $discountedVariant = ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Large',
        'sku' => 'PROMO-LARGE',
        'combination_hash' => hash('sha256', 'large'),
        'price' => 200,
        'stock' => 10,
        'is_active' => true,
    ]);
    $regularVariant = ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Small',
        'sku' => 'PROMO-SMALL',
        'combination_hash' => hash('sha256', 'small'),
        'price' => 100,
        'stock' => 10,
        'is_active' => true,
    ]);
    Coupon::query()->create([
        'code' => null,
        'is_automatic' => true,
        'target_type' => 'variant',
        'target_id' => $discountedVariant->id,
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'minimum_subtotal' => 0,
        'is_active' => true,
    ]);
    $cart = Cart::query()->create(['session_id' => session()->getId()]);
    foreach ([[$discountedVariant, 2], [$regularVariant, 1]] as [$variant, $quantity]) {
        $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'variant_name' => $variant->name,
            'line_key' => 'variant:' . $variant->id,
            'name' => $product->name,
            'price' => $variant->price,
            'quantity' => $quantity,
        ]);
    }
    session()->put('cart_id', $cart->id);

    $this->getJson('/api/cart')
        ->assertOk()
        ->assertJsonPath('items.0.promotion_discount', 80)
        ->assertJsonPath('items.0.promotion_name', 'Automatic offer')
        ->assertJsonPath('items.1.promotion_discount', 0)
        ->assertJsonPath('items.1.promotion_name', null);
});

it('shows a targeted product campaign on shop and product detail prices', function () {
    $product = Product::create([
        'name' => 'Campaign display product',
        'slug' => 'campaign-display-product',
        'price' => 100,
        'stock' => 10,
        'type' => 'simple',
        'is_active' => true,
    ]);
    Coupon::query()->create([
        'code' => null,
        'is_automatic' => true,
        'target_type' => 'product',
        'target_id' => $product->id,
        'discount_type' => 'percentage',
        'discount_value' => 20,
        'maximum_discount' => 30,
        'minimum_subtotal' => 300,
        'is_active' => true,
    ]);

    $this->get(route('shop'))
        ->assertOk()
        ->assertSee('৳80.00')
        ->assertSee('20% OFF · UP TO ৳30.00 OFF')
        ->assertSee('text-green-800', false)
        ->assertDontSee('Automatic offer')
        ->assertSee('On orders ৳300.00+');
    $this->get(route('products.show', ['product' => $product->slug]))
        ->assertOk()
        ->assertSee('৳80.00')
        ->assertSee('20% OFF · UP TO ৳30.00 OFF')
        ->assertDontSee('Automatic offer');
});

it('highlights an uncapped fixed amount and shows the coupon needed to get it', function () {
    $product = Product::create([
        'name' => 'Fixed discount display',
        'slug' => 'fixed-discount-display',
        'price' => 300,
        'stock' => 10,
        'type' => 'simple',
        'is_active' => true,
    ]);
    Coupon::query()->create([
        'code' => 'SAVE40',
        'target_type' => 'product',
        'target_id' => $product->id,
        'discount_type' => 'fixed',
        'discount_value' => 40,
        'minimum_subtotal' => 0,
        'is_active' => true,
    ]);

    $this->get(route('shop'))
        ->assertOk()
        ->assertSee('৳260.00')
        ->assertSee('৳40.00 OFF')
        ->assertSee('Use code SAVE40');
});

it('shows the discounted price for an eligible variant on product detail', function () {
    $product = Product::create([
        'name' => 'Variant campaign display',
        'slug' => 'variant-campaign-display',
        'type' => 'variable',
        'is_active' => true,
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'One litre',
        'sku' => 'VARIANT-DISPLAY-1L',
        'combination_hash' => hash('sha256', 'variant-display-1l'),
        'price' => 200,
        'stock' => 10,
        'is_active' => true,
    ]);
    Coupon::query()->create([
        'code' => 'LITRE15',
        'target_type' => 'variant',
        'target_id' => $variant->id,
        'discount_type' => 'percentage',
        'discount_value' => 12.5,
        'minimum_subtotal' => 0,
        'is_active' => true,
    ]);
    $loadedProduct = Product::query()->with('variants')->findOrFail($product->id);
    app(CouponService::class)->attachCatalogOffers(collect([$loadedProduct]));
    expect($loadedProduct->variants->first()->catalog_offer['price'])->toBe(175.0);

    $this->get(route('products.show', ['product' => $product->slug]))
        ->assertOk()
        ->assertSee('data-promotion-price="175"', false)
        ->assertSee('data-promotion-badge="12.5% OFF"', false)
        ->assertSee('Use code LITRE15');
});

it('requires a variant when adding a variable product to the cart', function () {
    $product = Product::create([
        'name' => 'Variable Shirt',
        'slug' => 'variable-shirt-cart',
        'type' => 'variable',
        'is_active' => true,
    ]);

    $this->postJson('/api/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('product_variant_id');

    $this->assertDatabaseMissing('cart_items', [
        'product_id' => $product->id,
    ]);
});

it('keeps variant cart lines distinct and blocks manually unavailable variants', function () {
    $product = Product::create([
        'name' => 'Variable Cart Shirt',
        'slug' => 'variable-cart-shirt',
        'type' => 'variable',
        'is_active' => true,
    ]);
    $color = ProductAttribute::create([
        'name' => 'Color',
        'slug' => 'cart-color',
        'is_active' => true,
    ]);
    $product->productAttributes()->attach($color);
    $red = $color->values()->create([
        'value' => 'Red',
        'slug' => 'cart-red',
        'is_active' => true,
    ]);
    $blue = $color->values()->create([
        'value' => 'Blue',
        'slug' => 'cart-blue',
        'is_active' => true,
    ]);
    $variantService = app(ProductVariantService::class);
    $redVariant = $variantService->createVariant($product, [
        'name' => 'Red',
        'sku' => 'CART-SHIRT-RED',
        'attribute_value_ids' => [$red->id],
        'price' => 25,
        'stock' => 2,
    ]);
    $blueVariant = $variantService->createVariant($product, [
        'name' => 'Blue',
        'sku' => 'CART-SHIRT-BLUE',
        'attribute_value_ids' => [$blue->id],
        'price' => 27,
        'stock' => 4,
    ]);

    $this->postJson('/api/cart/items', [
        'product_id' => $product->id,
        'product_variant_id' => $redVariant->id,
        'quantity' => 1,
        'price' => 0,
    ])->assertOk()->assertJsonPath('items.0.price', 25);

    $redAgainResponse = $this->postJson('/api/cart/items', [
        'product_id' => $product->id,
        'product_variant_id' => $redVariant->id,
        'quantity' => 1,
    ]);
    $this->assertDatabaseCount('carts', 1);
    $redAgainResponse->assertOk()->assertJsonFragment([
        'product_variant_id' => $redVariant->id,
        'quantity' => 2,
    ]);

    $blueResponse = $this->postJson('/api/cart/items', [
        'product_id' => $product->id,
        'product_variant_id' => $blueVariant->id,
        'quantity' => 1,
    ]);
    $blueResponse->assertOk()->assertJsonFragment([
        'product_variant_id' => $blueVariant->id,
        'price' => 27,
    ]);

    $this->postJson('/api/cart/items', [
        'product_id' => $product->id,
        'product_variant_id' => $redVariant->id,
        'quantity' => 1,
    ])->assertOk()->assertJsonFragment([
        'product_variant_id' => $redVariant->id,
        'quantity' => 3,
    ]);

    $redVariant->forceFill(['is_out_of_stock' => true])->save();

    $this->postJson('/api/cart/items', [
        'product_id' => $product->id,
        'product_variant_id' => $redVariant->id,
        'quantity' => 1,
    ])->assertUnprocessable()->assertJsonValidationErrors('product_variant_id');

    $this->assertDatabaseCount('cart_items', 2);
    $this->assertDatabaseHas('cart_items', [
        'product_variant_id' => $redVariant->id,
        'quantity' => 3,
        'price' => 25,
    ]);
});

it('checks out a selected variant without changing a stock quantity', function () {
    $product = Product::create([
        'name' => 'Checkout Variant Shirt',
        'slug' => 'checkout-variant-shirt',
        'type' => 'variable',
        'is_active' => true,
    ]);
    $size = ProductAttribute::create([
        'name' => 'Size',
        'slug' => 'checkout-size',
        'is_active' => true,
    ]);
    $product->productAttributes()->attach($size);
    $large = $size->values()->create([
        'value' => 'Large',
        'slug' => 'checkout-large',
        'is_active' => true,
    ]);
    $variant = app(ProductVariantService::class)->createVariant($product, [
        'sku' => 'CHECKOUT-SHIRT-LARGE',
        'attribute_value_ids' => [$large->id],
        'price' => 31,
    ]);
    $cart = Cart::query()->create(['session_id' => session()->getId()]);
    session()->put('cart_id', $cart->id);
    $cart->items()->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'variant_name' => $variant->displayName(),
        'line_key' => 'variant:' . $variant->id,
        'name' => $product->name,
        'price' => 1,
        'quantity' => 2,
    ]);

    $variant->forceFill(['is_out_of_stock' => true])->save();

    $this->post(route('checkout.store'), [
        'cart_id' => $cart->id,
        'customer_name' => 'John Doe',
        'phone' => '01712345678',
        'address' => 'House 12, Road 6, Dhanmondi',
        'city' => 'Dhaka',
        'payment_method' => 'cod',
    ])->assertRedirect()->assertSessionHasErrors('cart');

    $this->assertDatabaseCount('orders', 0);

    $variant->forceFill(['is_out_of_stock' => false])->save();

    $this->post(route('checkout.store'), [
        'cart_id' => $cart->id,
        'customer_name' => 'John Doe',
        'phone' => '01712345678',
        'address' => 'House 12, Road 6, Dhanmondi',
        'city' => 'Dhaka',
        'payment_method' => 'cod',
    ])->assertRedirect();

    $this->assertDatabaseHas('order_items', [
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'variant_name' => 'Size: Large',
        'variant_sku' => 'CHECKOUT-SHIRT-LARGE',
        'price' => 31,
        'quantity' => 2,
    ]);
    $this->assertDatabaseHas('product_variants', [
        'id' => $variant->id,
        'is_out_of_stock' => false,
    ]);
    $this->assertDatabaseCount('cart_items', 0);
});

it('rejects out-of-stock products from the cart API', function () {
    $product = Product::create([
        'name' => 'Unavailable Mango',
        'slug' => 'unavailable-mango',
        'price' => 240,
        'stock' => 12,
        'type' => 'simple',
        'is_active' => true,
    ]);
    $product->forceFill(['is_out_of_stock' => true])->save();

    $this->postJson('/api/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('product_id');

    $this->assertDatabaseMissing('cart_items', ['product_id' => $product->id]);
});

it('creates an order from the current cart', function () {
    $category = Category::create([
        'name' => 'Fresh Fruits',
        'slug' => 'fresh-fruits',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Fresh Mango',
        'slug' => 'fresh-mango',
        'sku' => 'MANGO-005',
        'short_description' => 'Sweet and juicy mango.',
        'price' => 240.00,
        'compare_at_price' => 299.00,
        'stock' => 20,
        'type' => 'simple',
        'is_active' => true,
    ]);

    $cart = Cart::query()->create([
        'session_id' => session()->getId(),
    ]);
    session()->put('cart_id', $cart->id);

    $cart->items()->create([
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => $product->price,
        'quantity' => 2,
    ]);

    $response = $this->postJson(route('checkout.store'), [
        'cart_id' => $cart->id,
        'customer_name' => 'John Doe',
        'phone' => '01712345678',
        'address' => 'House 12, Road 6, Dhanmondi',
        'city' => 'Dhaka',
        'area' => 'Dhanmondi',
        'payment_method' => 'cod',
        'notes' => 'Please call before delivery.',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('orders', [
        'customer_name' => 'John Doe',
        'phone' => '01712345678',
        'payment_method' => 'cod',
    ]);
    $this->assertDatabaseHas('order_items', [
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
});

it('limits the order success page to its owning account', function () {
    $customer = User::factory()->create(['account_type' => 'customer', 'status' => 'active']);
    $otherCustomer = User::factory()->create(['account_type' => 'customer', 'status' => 'active']);
    $customersOrder = Order::factory()->create(['user_id' => $customer->id]);
    $otherCustomersOrder = Order::factory()->create(['user_id' => $otherCustomer->id]);

    $this->actingAs($customer)
        ->get(route('checkout.success', $customersOrder))
        ->assertOk()
        ->assertSee($customersOrder->order_number);

    $this->get(route('checkout.success', $otherCustomersOrder))->assertNotFound();
});

it('uses a signed guest success URL and rejects unsigned access from another session', function () {
    $product = Product::create([
        'name' => 'Guest Checkout Product',
        'slug' => 'guest-checkout-product',
        'price' => 100,
        'stock' => 10,
        'type' => 'simple',
        'is_active' => true,
    ]);
    $cart = Cart::query()->create(['session_id' => session()->getId()]);
    session()->put('cart_id', $cart->id);
    $cart->items()->create([
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => $product->price,
        'quantity' => 1,
    ]);

    $checkoutResponse = $this->followingRedirects()->post(route('checkout.store'), [
        'customer_name' => 'Guest Success Customer',
        'phone' => '01710000007',
        'address' => 'House 7, Test Street',
        'city' => 'Dhaka',
        'area' => 'Dhanmondi',
        'payment_method' => 'cod',
    ]);
    $guestOrder = Order::query()->where('customer_name', 'Guest Success Customer')->firstOrFail();
    $otherSessionOrder = Order::factory()->create([
        'user_id' => null,
        'session_id' => 'another-session-id',
    ]);

    $checkoutResponse->assertOk()
        ->assertSee($guestOrder->order_number);

    $this->get(route('checkout.success', $otherSessionOrder))->assertNotFound();
});

it('rejects checkout when a product in the cart is out of stock', function () {
    $product = Product::create([
        'name' => 'Unavailable Bread',
        'slug' => 'unavailable-bread',
        'price' => 110,
        'stock' => 20,
        'type' => 'simple',
        'is_active' => true,
    ]);
    $product->forceFill(['is_out_of_stock' => true])->save();

    $cart = Cart::query()->create(['session_id' => session()->getId()]);
    $cart->items()->create([
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => $product->price,
        'quantity' => 1,
    ]);

    $this->post(route('checkout.store'), [
        'cart_id' => $cart->id,
        'customer_name' => 'John Doe',
        'phone' => '01712345678',
        'address' => 'House 12, Road 6, Dhanmondi',
        'city' => 'Dhaka',
        'payment_method' => 'cod',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors('cart');

    $this->assertDatabaseCount('orders', 0);
    $this->assertDatabaseHas('cart_items', ['product_id' => $product->id]);
});

it('shows the system owner dashboard with permissions and maintenance overview', function () {
    $owner = completeManagementTwoFactorForTest(createSystemOwnerUser());
    $this->actingAs($owner);

    $response = $this->get(route('admin'));

    $response->assertOk();
    $response->assertSee('System Owner Overview');
    $response->assertSee('Current User Permissions');
    $response->assertSee('Maintainable Actions');
    $response->assertSee('System Owner');
    $response->assertSee('Manage Products');
});

it('loads the checkout page', function () {
    config()->set('payments.mobile_wallets.bkash.merchant_number', '01700000001');
    config()->set('payments.mobile_wallets.nagad.merchant_number', '01800000002');
    config()->set('payments.mobile_wallets.rocket.merchant_number', '01900000003');

    $response = $this->get(route('checkout'));

    $response->assertOk();
    $response->assertSee('Cash on delivery');
    $response->assertSee('bKash');
    $response->assertSee('Nagad');
    $response->assertSee('Rocket');
    $response->assertSee('data-wallet-logo="rocket"', false);
    $response->assertSee('images/payments/bkash.png');
    $response->assertSee('images/payments/nagad.png');
    $response->assertSee('images/payments/rocket.png');
    $response->assertSee('data-copy-payment-number="01900000003"', false);
    $response->assertSee('Send exactly ৳0.00 to the selected merchant number');
    $response->assertSee('01800000002');
    $response->assertDontSee('৳ 01800000002');
    $response->assertSee('data-payment-details', false);
});

it('uses the configured delivery fee in checkout and the saved order', function () {
    $settingsUser = User::factory()->create();
    app(SiteSettings::class)->update([
        'delivery_fee_inside_dhaka' => '45.50',
        'delivery_fee_outside_dhaka' => '85.50',
    ], $settingsUser->id);

    $product = Product::create([
        'name' => 'Delivery Fee Product',
        'slug' => 'delivery-fee-product',
        'price' => 120,
        'stock' => 5,
        'type' => 'simple',
        'is_active' => true,
    ]);
    $cart = Cart::query()->create(['session_id' => session()->getId()]);
    $cart->items()->create([
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => $product->price,
        'quantity' => 1,
    ]);
    session()->put('cart_id', $cart->id);

    $this->get(route('checkout'))
        ->assertSee('৳85.50')
        ->assertSee('৳205.50');

    $this->post(route('checkout.store'), [
        'customer_name' => 'Delivery Fee Customer',
        'phone' => '01710000008',
        'address' => 'House 8, Test Street',
        'city' => 'Dhaka',
        'payment_method' => 'cod',
    ])->assertRedirect();

    $order = Order::query()->where('phone', '01710000008')->firstOrFail();
    expect((float) $order->delivery_fee)->toBe(45.5)
        ->and((float) $order->total)->toBe(165.5);
});

it('uses the outside Dhaka delivery fee for cities beyond Dhaka', function () {
    $settingsUser = User::factory()->create();
    app(SiteSettings::class)->update([
        'delivery_fee_inside_dhaka' => '45.50',
        'delivery_fee_outside_dhaka' => '85.50',
    ], $settingsUser->id);

    $product = Product::create([
        'name' => 'Outside Dhaka Delivery Product',
        'slug' => 'outside-dhaka-delivery-product',
        'price' => 120,
        'stock' => 5,
        'type' => 'simple',
        'is_active' => true,
    ]);
    $cart = Cart::query()->create(['session_id' => session()->getId()]);
    $cart->items()->create([
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => $product->price,
        'quantity' => 1,
    ]);
    session()->put('cart_id', $cart->id);

    $this->get(route('checkout'))
        ->assertSee('৳85.50')
        ->assertSee('৳205.50');

    $this->post(route('checkout.store'), [
        'customer_name' => 'Outside Dhaka Delivery Customer',
        'phone' => '01710000009',
        'address' => 'House 9, Test Street',
        'city' => 'Chattogram',
        'payment_method' => 'cod',
    ])->assertRedirect();

    $order = Order::query()->where('phone', '01710000009')->firstOrFail();
    expect((float) $order->delivery_fee)->toBe(85.5)
        ->and((float) $order->total)->toBe(205.5);
});

it('rejects online checkout until a payment gateway is configured', function () {
    $this->post(route('checkout.store'), [
        'customer_name' => 'Unpaid Customer',
        'phone' => '01710000008',
        'address' => 'House 8, Test Street',
        'city' => 'Dhaka',
        'payment_method' => 'online',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors('payment_method');

    $this->assertDatabaseCount('orders', 0);
});

it('rejects Rocket checkout until a merchant number is configured', function () {
    config()->set('payments.mobile_wallets.rocket.merchant_number', null);

    $this->post(route('checkout.store'), [
        'customer_name' => 'Unconfigured Rocket Customer',
        'phone' => '01710000008',
        'address' => 'House 8, Test Street',
        'city' => 'Dhaka',
        'payment_method' => 'rocket',
        'payment_sender_phone' => '01710000008',
        'payment_transaction_id' => 'ROCKET-UNCONFIGURED-1004',
        'payment_proof' => UploadedFile::fake()->image('rocket-receipt.jpg'),
    ])
        ->assertRedirect()
        ->assertSessionHasErrors('payment_method');

    $this->assertDatabaseCount('orders', 0);
});

it('creates a pending bKash order with private receipt details', function () {
    config()->set('payments.mobile_wallets.bkash.merchant_number', '01700000001');
    Storage::fake('local');

    $product = Product::create([
        'name' => 'Mobile Payment Product',
        'slug' => 'mobile-payment-product',
        'price' => 120,
        'stock' => 5,
        'type' => 'simple',
        'is_active' => true,
    ]);
    $cart = Cart::query()->create(['session_id' => session()->getId()]);
    session()->put('cart_id', $cart->id);
    $cart->items()->create([
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => $product->price,
        'quantity' => 1,
    ]);

    $response = $this->post(route('checkout.store'), [
        'customer_name' => 'Mobile Wallet Customer',
        'phone' => '01712345678',
        'address' => 'House 12, Test Street',
        'city' => 'Dhaka',
        'payment_method' => 'bkash',
        'payment_sender_phone' => '01711112222',
        'payment_transaction_id' => 'BKASH-TXN-1001',
        'payment_proof' => UploadedFile::fake()->image('receipt.jpg'),
    ]);

    $order = Order::query()->where('payment_transaction_id', 'BKASH-TXN-1001')->firstOrFail();

    $response->assertRedirect()->assertSessionHasNoErrors();
    expect($order->payment_status)->toBe('pending')
        ->and((float) $order->payment_amount)->toBe(180.0)
        ->and($order->payment_method)->toBe('bkash');
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'payment_sender_phone' => '01711112222',
        'payment_transaction_id' => 'BKASH-TXN-1001',
        'payment_amount' => 180,
    ]);
    Storage::disk('local')->assertExists($order->payment_proof_path);
});

it('creates a pending Rocket order with private receipt details', function () {
    config()->set('payments.mobile_wallets.rocket.merchant_number', '01900000003');
    Storage::fake('local');

    $product = Product::create([
        'name' => 'Rocket Payment Product',
        'slug' => 'rocket-payment-product',
        'price' => 120,
        'stock' => 5,
        'type' => 'simple',
        'is_active' => true,
    ]);
    $cart = Cart::query()->create(['session_id' => session()->getId()]);
    session()->put('cart_id', $cart->id);
    $cart->items()->create([
        'product_id' => $product->id,
        'name' => $product->name,
        'price' => $product->price,
        'quantity' => 1,
    ]);

    $response = $this->post(route('checkout.store'), [
        'customer_name' => 'Rocket Wallet Customer',
        'phone' => '01712345678',
        'address' => 'House 19, Test Street',
        'city' => 'Dhaka',
        'payment_method' => 'rocket',
        'payment_sender_phone' => '01711112223',
        'payment_transaction_id' => 'ROCKET-TXN-1003',
        'payment_proof' => UploadedFile::fake()->image('rocket-receipt.jpg'),
    ]);

    $response->assertRedirect()->assertSessionHasNoErrors();
    $order = Order::query()->where('payment_transaction_id', 'ROCKET-TXN-1003')->firstOrFail();

    expect($order->payment_status)->toBe('pending')
        ->and((float) $order->payment_amount)->toBe(180.0)
        ->and($order->payment_method)->toBe('rocket');
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'payment_sender_phone' => '01711112223',
        'payment_transaction_id' => 'ROCKET-TXN-1003',
        'payment_amount' => 180,
    ]);
    Storage::disk('local')->assertExists($order->payment_proof_path);
});

it('requires transaction details and a receipt for mobile wallet checkout', function () {
    config()->set('payments.mobile_wallets.nagad.merchant_number', '01800000002');
    config()->set('payments.mobile_wallets.rocket.merchant_number', '01900000003');

    foreach (['nagad', 'rocket'] as $provider) {
        $this->post(route('checkout.store'), [
            'customer_name' => 'Incomplete Payment Customer',
            'phone' => '01710000008',
            'address' => 'House 8, Test Street',
            'city' => 'Dhaka',
            'payment_method' => $provider,
        ])
            ->assertRedirect()
            ->assertSessionHasErrors(['payment_sender_phone', 'payment_transaction_id', 'payment_proof']);
    }

    $this->assertDatabaseCount('orders', 0);
});

it('lets an authorized order manager confirm a pending wallet payment', function () {
    $orderManager = completeManagementTwoFactorForTest(createSystemOwnerUser());
    $order = Order::factory()->create([
        'payment_method' => 'nagad',
        'payment_transaction_id' => 'NAGAD-TXN-1002',
        'payment_status' => 'pending',
    ]);

    $response = $this->actingAs($orderManager)->patch(route('admin.orders.payment.review', $order), [
        'payment_status' => 'paid',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'payment_status' => 'paid',
        'payment_reviewed_by' => $orderManager->id,
    ]);
});

it('displays product detail carousel with all product images and counter', function () {
    $category = Category::create([
        'name' => 'Organic Honey',
        'slug' => 'organic-honey',
        'is_active' => true,
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Sundarban Raw Honey',
        'slug' => 'sundarban-raw-honey',
        'sku' => 'HNY-001',
        'short_description' => '100% natural raw forest honey.',
        'price' => 650.00,
        'stock' => 15,
        'type' => 'simple',
        'is_active' => true,
    ]);

    ProductImage::create([
        'product_id' => $product->id,
        'image_path' => 'products/honey-front.jpg',
        'alt_text' => 'Front view',
        'is_primary' => true,
        'sort_order' => 0,
    ]);

    ProductImage::create([
        'product_id' => $product->id,
        'image_path' => 'products/honey-nutrition.jpg',
        'alt_text' => 'Nutrition facts',
        'is_primary' => false,
        'sort_order' => 1,
    ]);

    ProductImage::create([
        'product_id' => $product->id,
        'image_path' => 'products/honey-back.jpg',
        'alt_text' => 'Back view',
        'is_primary' => false,
        'sort_order' => 2,
    ]);

    $response = $this->get(route('products.show', ['product' => $product->slug]));

    $response->assertOk();
    $response->assertSee('id="product-carousel-track"', false);
    $response->assertSee('products/honey-front.jpg');
    $response->assertSee('products/honey-nutrition.jpg');
    $response->assertSee('products/honey-back.jpg');
    $response->assertSee('1 / 3');
    $response->assertSee('★ Primary');
    $response->assertSee('id="carousel-prev-btn"', false);
    $response->assertSee('id="carousel-next-btn"', false);
});

it('shows active variant options and routes variable products to option selection', function () {
    $product = Product::create([
        'name' => 'Variant Detail Shirt',
        'slug' => 'variant-detail-shirt',
        'type' => 'variable',
        'is_active' => true,
    ]);
    $color = ProductAttribute::create([
        'name' => 'Color',
        'slug' => 'detail-color',
        'is_active' => true,
    ]);
    $product->productAttributes()->attach($color);
    $red = $color->values()->create([
        'value' => 'Red',
        'slug' => 'detail-red',
        'is_active' => true,
    ]);
    app(ProductVariantService::class)->createVariant($product, [
        'sku' => 'DETAIL-SHIRT-RED',
        'attribute_value_ids' => [$red->id],
        'price' => 29,
    ]);
    $blue = $color->values()->create([
        'value' => 'Blue',
        'slug' => 'detail-blue',
        'is_active' => true,
    ]);
    app(ProductVariantService::class)->createVariant($product, [
        'sku' => 'DETAIL-SHIRT-BLUE',
        'attribute_value_ids' => [$blue->id],
        'price' => 30,
        'is_active' => false,
    ]);

    $this->get(route('products.show', $product->slug))
        ->assertOk()
        ->assertSee('data-product-card', false)
        ->assertSee('data-product-variant-select', false)
        ->assertSee('Color: Red')
        ->assertSee('Select an available option to continue.');

    $variant = $product->variants()->firstOrFail();
    $variant->forceFill(['is_out_of_stock' => true])->save();

    $this->get(route('products.show', $product->slug))
        ->assertOk()
        ->assertSee('Color: Red')
        ->assertSee('Out of stock')
        ->assertSee('data-unavailable="true"', false)
        ->assertSee('disabled', false);

    $this->get(route('shop'))
        ->assertOk()
        ->assertSee('Out of stock')
        ->assertSee('disabled', false)
        ->assertDontSee('data-select-variant', false);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Variant Detail Shirt')
        ->assertSee('Out of stock')
        ->assertSee('disabled', false);
});

it('displays designated primary image on shop and home product cards', function () {
    $category = Category::create([
        'name' => 'Beverages',
        'slug' => 'beverages',
        'is_active' => true,
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Green Tea Pack',
        'slug' => 'green-tea-pack',
        'sku' => 'TEA-001',
        'short_description' => 'Refreshing organic green tea.',
        'price' => 320.00,
        'stock' => 40,
        'type' => 'simple',
        'is_active' => true,
    ]);

    // Secondary image
    ProductImage::create([
        'product_id' => $product->id,
        'image_path' => 'products/tea-box-side.jpg',
        'alt_text' => 'Tea side',
        'is_primary' => false,
        'sort_order' => 1,
    ]);

    // Primary image
    ProductImage::create([
        'product_id' => $product->id,
        'image_path' => 'products/tea-box-front-primary.jpg',
        'alt_text' => 'Tea front primary',
        'is_primary' => true,
        'sort_order' => 0,
    ]);

    $shopResponse = $this->get(route('shop'));
    $shopResponse->assertOk();
    $shopResponse->assertSee('products/tea-box-front-primary.jpg');

    $homeResponse = $this->get(route('home'));
    $homeResponse->assertOk();
    $homeResponse->assertSee('products/tea-box-front-primary.jpg');
});

it('includes product image in cart payload and displays it on checkout', function () {
    $category = Category::create([
        'name' => 'Bakery',
        'slug' => 'bakery',
        'is_active' => true,
    ]);

    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Garlic Bread',
        'slug' => 'garlic-bread',
        'sku' => 'BRD-001',
        'price' => 110.00,
        'stock' => 20,
        'type' => 'simple',
        'is_active' => true,
    ]);

    ProductImage::create([
        'product_id' => $product->id,
        'image_path' => 'products/garlic-bread-thumb.jpg',
        'is_primary' => true,
        'sort_order' => 0,
    ]);

    $cartResponse = $this->postJson('/api/cart/items', [
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $cartResponse->assertOk();
    $cartResponse->assertJsonPath('items.0.image_url', '/storage/products/garlic-bread-thumb.jpg');

    $checkoutResponse = $this->get(route('checkout'));
    $checkoutResponse->assertOk();
    $checkoutResponse->assertSee('products/garlic-bread-thumb.jpg');
});
