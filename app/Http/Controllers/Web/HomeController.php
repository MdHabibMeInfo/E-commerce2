<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;

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
                'products.price',
                'products.compare_at_price',
                'products.is_out_of_stock',
            ])
            ->withSum('orderItems as units_sold', 'quantity')
            ->with([
                'primaryImage',
                'images:id,product_id,image_path,alt_text,is_primary,sort_order',
            ])
            ->orderBy('is_out_of_stock')
            ->orderByDesc('units_sold')
            ->orderByDesc('products.id')
            ->limit(4)
            ->get();

        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with(['products' => function ($query) {
                $query
                    ->where('is_active', true)
                    ->with([
                        'category:id,name,slug',
                        'primaryImage',
                        'images:id,product_id,image_path,alt_text,is_primary,sort_order',
                    ])
                    ->orderBy('is_out_of_stock')
                    ->orderByDesc('id')
                    ->limit(10);
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(12)
            ->get([
                'id',
                'name',
                'slug',
                'image',
            ]);

        $featuredProducts = Product::query()
            ->where('is_active', true)
            ->with([
                'category:id,name,slug',
                'primaryImage',
                'images:id,product_id,image_path,alt_text,is_primary,sort_order',
            ])
            ->orderBy('is_out_of_stock')
            ->latest('id')
            ->limit(12)
            ->get([
                'id',
                'category_id',
                'name',
                'slug',
                'short_description',
                'price',
                'compare_at_price',
                'is_out_of_stock',
            ]);

        $benefits = config('storefront.home_benefits', []);

        return view('storefront.home', [
            'topSellingProducts' => $topSellingProducts,
            'categories' => $categories,
            'featuredProducts' => $featuredProducts,
            'benefits' => $benefits,
        ]);
    }
}
