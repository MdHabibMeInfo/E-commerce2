<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function show(string $product): View
    {
        $product = Product::query()
            ->where('slug', $product)
            ->where('is_active', true)
            ->with([
                'category:id,name,slug',
                'primaryImage',
                'images' => function ($query) {
                    $query
                        ->orderByDesc('is_primary')
                        ->orderBy('sort_order')
                        ->orderBy('id');
                },
                'variants',
                'productAttributes',
            ])
            ->firstOrFail();

        return view('storefront.products.show', [
            'product' => $product,
        ]);
    }
}
