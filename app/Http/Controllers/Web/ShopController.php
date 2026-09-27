<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request, ?string $category = null): View
    {
        $activeCategory = null;

        if ($category !== null && $category !== '') {
            $activeCategory = Category::query()
                ->where('slug', $category)
                ->where('is_active', true)
                ->firstOrFail();
        }

        $sort = $request->query('sort', 'latest');
        $priceRange = $request->query('price_range', 'all');
        $minPrice = (float) $request->query('min_price', 0);
        $maxPrice = (float) $request->query('max_price', 0);
        $selectedVariants = collect($request->query('variants', $request->query('variant', [])))
            ->flatten()
            ->map(fn ($variant) => strtolower(trim((string) $variant)))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $flag = trim((string) $request->query('flag', ''));

        $variantOptions = ProductVariant::query()
            ->where('is_active', true)
            ->whereNotNull('name')
            ->select('name')
            ->distinct()
            ->orderBy('name')
            ->get()
            ->map(fn ($variant) => [
                'value' => strtolower(trim((string) $variant->name)),
                'label' => trim((string) $variant->name),
            ])
            ->values()
            ->all();

        $productsQuery = Product::query()
            ->where('is_active', true)
            ->with([
                'category:id,name,slug',
                'images' => function ($query) {
                    $query
                        ->orderByDesc('is_primary')
                        ->orderBy('sort_order')
                        ->orderBy('id');
                },
                'variants' => function ($query) {
                    $query->where('is_active', true);
                },
            ]);

        if ($activeCategory) {
            $productsQuery->where('category_id', $activeCategory->id);
        }

        if ($request->filled('q')) {
            $search = trim($request->q);

            $productsQuery->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($priceRange === 'under_200') {
            $productsQuery->where('price', '<', 200);
        } elseif ($priceRange === '200_500') {
            $productsQuery->whereBetween('price', [200, 500]);
        } elseif ($priceRange === '500_plus') {
            $productsQuery->where('price', '>=', 500);
        }

        $maxProductPrice = (float) (Product::query()
            ->where('is_active', true)
            ->max('price') ?? 0);

        if ($maxPrice === 0) {
            $maxPrice = $maxProductPrice;
        }

        if ($minPrice > 0 || $maxPrice > 0) {
            $effectiveMinPrice = $minPrice > 0 ? $minPrice : 0;
            $effectiveMaxPrice = $maxPrice > 0 ? $maxPrice : $maxProductPrice;

            if ($effectiveMinPrice > 0 && $effectiveMaxPrice > 0) {
                $productsQuery->whereBetween('price', [$effectiveMinPrice, $effectiveMaxPrice]);
            } elseif ($effectiveMinPrice > 0) {
                $productsQuery->where('price', '>=', $effectiveMinPrice);
            } elseif ($effectiveMaxPrice > 0) {
                $productsQuery->where('price', '<=', $effectiveMaxPrice);
            }
        }

        if (! empty($selectedVariants)) {
            $productsQuery->whereHas('variants', function ($query) use ($selectedVariants) {
                $query->where(function ($variantQuery) use ($selectedVariants) {
                    foreach ($selectedVariants as $variantName) {
                        $variantQuery->orWhereRaw('LOWER(name) = ?', [$variantName]);
                    }
                });
            });
        }

        if ($flag !== '') {
            $flagKey = strtolower($flag);

            if ($flagKey === 'best-selling') {
                $productsQuery->where(function ($query) {
                    $query
                        ->where('name', 'like', '%best%')
                        ->orWhere('short_description', 'like', '%best%');
                });
            }

            if ($flagKey === 'new-arrival') {
                $productsQuery->where(function ($query) {
                    $query
                        ->where('name', 'like', '%new%')
                        ->orWhere('short_description', 'like', '%new%');
                });
            }
        }

        match ($sort) {
            'price_asc' => $productsQuery->orderBy('price', 'asc'),
            'price_desc' => $productsQuery->orderBy('price', 'desc'),
            'name_asc' => $productsQuery->orderBy('name', 'asc'),
            'name_desc' => $productsQuery->orderBy('name', 'desc'),
            default => $productsQuery->orderByDesc('id'),
        };

        $products = $productsQuery->get();

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'image']);

        return view('storefront.shop', [
            'products' => $products,
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'searchQuery' => $request->query('q', ''),
            'sort' => $sort,
            'priceRange' => $priceRange,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'selectedVariants' => $selectedVariants,
            'variantOptions' => $variantOptions,
            'flag' => $flag,
            'maxProductPrice' => $maxProductPrice,
        ]);
    }
}
