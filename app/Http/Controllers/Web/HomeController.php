<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class HomeController extends Controller
{
    public function index(): View
    {
        $topSellingProducts = Product::query()
            ->where('is_active', true)
            ->select([
                'products.id',
                'products.category_id',
                'products.name',
                'products.slug',
                'products.type',
                'products.price',
                'products.compare_at_price',
                'products.is_out_of_stock',
            ])
            ->withSum('orderItems as units_sold', 'quantity')
            ->with([
                'primaryImage',
                'images:id,product_id,image_path,alt_text,is_primary,sort_order',
                'variants:id,product_id,is_active,is_out_of_stock',
            ])
            ->orderBy('is_out_of_stock')
            ->orderByDesc('units_sold')
            ->orderByDesc('products.id')
            ->limit(4)
            ->get();

        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(12)
            ->get([
                'id',
                'name',
                'slug',
                'image',
            ]);

        $categoryRows = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->whereHas('products', function ($query) {
                $query->where('is_active', true);
            })
            ->with(['products' => function ($query) {
                $query
                    ->where('is_active', true)
                    ->with([
                        'primaryImage',
                        'images:id,product_id,image_path,alt_text,is_primary,sort_order',
                        'variants:id,product_id,is_active,is_out_of_stock',
                    ])
                    ->orderBy('is_out_of_stock')
                    ->orderByDesc('id')
                    ->limit(10);
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(5)
            ->get([
                'id',
                'name',
                'slug',
            ]);

        $featuredProducts = $this->homeProductsQuery()
            ->orderBy('is_out_of_stock')
            ->latest('id')
            ->limit(12)
            ->get();

        $newArrivals = $this->homeProductsQuery()
            ->latest('created_at')
            ->latest('id')
            ->limit(12)
            ->get();

        $justForYouProducts = $this->homeProductsQuery()
            ->where('is_featured', true)
            ->orderBy('is_out_of_stock')
            ->latest('id')
            ->limit(8)
            ->get();

        if ($justForYouProducts->isEmpty()) {
            $justForYouProducts = $newArrivals->take(8);
        }

        $approvedReviews = ProductReview::query()
            ->where('is_approved', true)
            ->whereHas('product', fn ($query) => $query->where('is_active', true))
            ->with('product:id,name,slug')
            ->latest()
            ->limit(6)
            ->get();

        $benefits = config('storefront.home_benefits', []);

        return view('storefront.home', [
            'topSellingProducts' => $topSellingProducts,
            'categories' => $categories,
            'categoryRows' => $categoryRows,
            'featuredProducts' => $featuredProducts,
            'newArrivals' => $newArrivals,
            'justForYouProducts' => $justForYouProducts,
            'approvedReviews' => $approvedReviews,
            'benefits' => $benefits,
        ]);
    }

    private function homeProductsQuery(): Builder
    {
        return Product::query()
            ->where('is_active', true)
            ->select([
                'products.id',
                'products.category_id',
                'products.name',
                'products.slug',
                'products.type',
                'products.short_description',
                'products.price',
                'products.compare_at_price',
                'products.is_out_of_stock',
            ])
            ->with([
                'category:id,name,slug',
                'primaryImage',
                'images:id,product_id,image_path,alt_text,is_primary,sort_order',
                'variants:id,product_id,is_active,is_out_of_stock',
            ]);
    }
}
