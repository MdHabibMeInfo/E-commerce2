<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function show(Request $request, string $product): View
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
                'variants' => fn ($query) => $query
                    ->where('is_active', true)
                    ->with('attributeValues.productAttribute'),
                'productAttributes',
                'approvedReviews' => fn ($query) => $query->latest()->limit(8),
            ])
            ->withCount('approvedReviews as reviews_count')
            ->withAvg('approvedReviews as reviews_avg_rating', 'rating')
            ->firstOrFail();
        $selectedVariant = $product->variants->firstWhere('id', $request->integer('variant'));
        $selectedVariantId = $selectedVariant
            && ! $selectedVariant->is_out_of_stock
            && ! $product->is_out_of_stock
                ? (string) $selectedVariant->getKey()
                : null;
        $relatedProducts = Product::query()
            ->where('is_active', true)
            ->whereKeyNot($product->getKey())
            ->when($product->category_id !== null, fn ($query) => $query
                ->orderByRaw('CASE WHEN category_id = ? THEN 0 ELSE 1 END', [$product->category_id]))
            ->with(['primaryImage', 'images', 'variants'])
            ->latest()
            ->limit(10)
            ->get();

        return view('storefront.products.show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'selectedVariantId' => $selectedVariantId,
        ]);
    }
}
