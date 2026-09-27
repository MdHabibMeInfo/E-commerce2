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

        $featuredProducts = Product::query()
            ->where('is_active', true)
            ->with([
                'category:id,name,slug',
                'images:id,product_id,image_path,alt_text,is_primary,sort_order',
            ])
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
            ]);

        $benefits = config('storefront.home_benefits', []);

        return view('storefront.home', [
            'categories' => $categories,
            'featuredProducts' => $featuredProducts,
            'benefits' => $benefits,
        ]);
    }
}
