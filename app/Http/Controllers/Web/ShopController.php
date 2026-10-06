<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function suggestions(Request $request): JsonResponse
    {
        $search = trim((string) $request->validate([
            'search' => ['required', 'string', 'min:2', 'max:100'],
        ])['search']);

        if (mb_strlen($search) < 2) {
            return response()->json([]);
        }

        $prefix = $search.'%';

        $suggestions = Product::query()
            ->where('is_active', true)
            ->where(function ($query) use ($prefix) {
                $query
                    ->where('name', 'like', $prefix)
                    ->orWhere('sku', 'like', $prefix);
            })
            ->with([
                'category:id,name',
                'images:id,product_id,image_path,alt_text,is_primary,sort_order',
                'primaryImage:id,product_id,image_path,alt_text',
            ])
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$prefix])
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'category_id', 'name', 'slug', 'price'])
            ->map(function (Product $product): array {
                $image = $product->primaryImage
                    ?? $product->images->firstWhere('is_primary', true)
                    ?? $product->images->first();

                return [
                    'name' => $product->name,
                    'category' => $product->category?->name,
                    'price' => $product->price,
                    'image' => $image ? '/storage/'.ltrim($image->image_path, '/') : null,
                    'url' => route('products.show', ['product' => $product->slug]),
                ];
            })
            ->all();

        return response()->json($suggestions);
    }

    public function index(Request $request, ?string $category = null): View
    {
        $activeCategory = null;

        if ($category !== null && $category !== '') {
            $activeCategory = Category::query()
                ->where('slug', $category)
                ->where('is_active', true)
                ->firstOrFail();
        }

        $requestedSort = $request->query('sort', 'default');
        $sort = is_string($requestedSort)
            && in_array($requestedSort, ['default', 'latest', 'oldest', 'price_asc', 'price_desc', 'name_asc', 'name_desc'], true)
                ? $requestedSort
                : 'default';
        $requestedPerPage = $request->query('per_page', 'default');
        $perPageSelection = is_string($requestedPerPage)
            && in_array($requestedPerPage, ['default', '16', '20', '24', '36'], true)
                ? $requestedPerPage
                : 'default';
        $perPage = $perPageSelection === 'default' ? 16 : (int) $perPageSelection;
        $requestedPriceRange = $request->query('price_range', 'all');
        $priceRange = is_string($requestedPriceRange) ? $requestedPriceRange : 'all';
        $requestedMinPrice = $request->query('min_price');
        $minPrice = is_numeric($requestedMinPrice) && is_finite((float) $requestedMinPrice)
            ? max(0, (float) $requestedMinPrice)
            : 0;
        $requestedMaxPrice = $request->query('max_price');
        $maxPrice = is_numeric($requestedMaxPrice) && is_finite((float) $requestedMaxPrice)
            ? max(0, (float) $requestedMaxPrice)
            : 0;
        $requestedMaxPrice = $maxPrice;
        $requestedVariants = $request->query('variants', $request->query('variant', []));
        $selectedVariants = collect($requestedVariants)
            ->flatten()
            ->filter(fn ($variant) => is_string($variant) || is_numeric($variant))
            ->map(fn ($variant) => strtolower(trim((string) $variant)))
            ->filter()
            ->unique()
            ->take(20)
            ->values()
            ->all();
        $requestedFlag = $request->query('flag', '');
        $flag = is_string($requestedFlag) ? trim($requestedFlag) : '';
        $requestedSearchQuery = $request->query('q', '');
        $searchQuery = is_string($requestedSearchQuery) ? trim($requestedSearchQuery) : '';

        $variantOptions = ProductVariant::query()
            ->where('is_active', true)
            ->whereNotNull('name')
            ->whereHas('product', function ($query) use ($activeCategory) {
                $query->where('is_active', true);

                if ($activeCategory) {
                    $query->where('category_id', $activeCategory->id);
                }
            })
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
                'primaryImage',
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

        if ($searchQuery !== '') {
            $productsQuery->where(function ($query) use ($searchQuery) {
                $query
                    ->where('name', 'like', "%{$searchQuery}%")
                    ->orWhere('short_description', 'like', "%{$searchQuery}%")
                    ->orWhere('sku', 'like', "%{$searchQuery}%");
            });
        }

        if ($priceRange === 'under_200') {
            $productsQuery->where('price', '<', 200);
        } elseif ($priceRange === '200_500') {
            $productsQuery->whereBetween('price', [200, 500]);
        } elseif ($priceRange === '500_plus') {
            $productsQuery->where('price', '>=', 500);
        }

        $maxProductPriceQuery = Product::query()->where('is_active', true);

        if ($activeCategory) {
            $maxProductPriceQuery->where('category_id', $activeCategory->id);
        }

        $maxProductPrice = (float) ($maxProductPriceQuery->max('price') ?? 0);
        $maxVariantPrice = (float) (ProductVariant::query()
            ->where('is_active', true)
            ->whereHas('product', function (Builder $query) use ($activeCategory): void {
                $query->where('is_active', true);

                if ($activeCategory) {
                    $query->where('category_id', $activeCategory->id);
                }
            })
            ->max('price') ?? 0);
        $maxProductPrice = max($maxProductPrice, $maxVariantPrice);

        if ($maxPrice <= 0 || $maxPrice > $maxProductPrice) {
            $maxPrice = $maxProductPrice;
        }

        if ($requestedMaxPrice >= $maxProductPrice) {
            $requestedMaxPrice = 0;
        }

        if ($minPrice > 0 || ($maxPrice > 0 && $maxPrice < $maxProductPrice)) {
            $effectiveMinPrice = $minPrice > 0 ? $minPrice : 0;
            $effectiveMaxPrice = $maxPrice > 0 ? $maxPrice : $maxProductPrice;

            $productsQuery->where(function (Builder $query) use ($effectiveMinPrice, $effectiveMaxPrice): void {
                $query->where(function (Builder $productPriceQuery) use ($effectiveMinPrice, $effectiveMaxPrice): void {
                    $productPriceQuery->whereNotNull('price');

                    if ($effectiveMinPrice > 0) {
                        $productPriceQuery->where('price', '>=', $effectiveMinPrice);
                    }

                    if ($effectiveMaxPrice > 0) {
                        $productPriceQuery->where('price', '<=', $effectiveMaxPrice);
                    }
                })->orWhereHas('variants', function (Builder $variantQuery) use ($effectiveMinPrice, $effectiveMaxPrice): void {
                    $variantQuery->where('is_active', true);

                    if ($effectiveMinPrice > 0) {
                        $variantQuery->where('price', '>=', $effectiveMinPrice);
                    }

                    if ($effectiveMaxPrice > 0) {
                        $variantQuery->where('price', '<=', $effectiveMaxPrice);
                    }
                });
            });
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

        if (in_array($sort, ['price_asc', 'price_desc'], true)) {
            $productsQuery->withMin([
                'variants as active_min_variant_price' => fn (Builder $query) => $query->where('is_active', true),
            ], 'price');
        }

        $productsQuery->orderBy('is_out_of_stock');

        match ($sort) {
            'oldest' => $productsQuery->orderBy('id'),
            'price_asc' => $productsQuery
                ->orderByRaw('COALESCE(CASE WHEN products.type = ? THEN active_min_variant_price END, products.price) ASC', ['variable'])
                ->orderBy('products.id'),
            'price_desc' => $productsQuery
                ->orderByRaw('COALESCE(CASE WHEN products.type = ? THEN active_min_variant_price END, products.price) DESC', ['variable'])
                ->orderBy('products.id'),
            'name_asc' => $productsQuery->orderBy('name', 'asc'),
            'name_desc' => $productsQuery->orderBy('name', 'desc'),
            'default', 'latest' => $productsQuery->orderByDesc('id'),
            default => $productsQuery->orderByDesc('id'),
        };

        $products = $productsQuery->paginate($perPage)->withQueryString();

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'image']);

        return view('storefront.shop', [
            'products' => $products,
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'searchQuery' => $searchQuery,
            'sort' => $sort,
            'perPageSelection' => $perPageSelection,
            'priceRange' => $priceRange,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'requestedMaxPrice' => $requestedMaxPrice,
            'selectedVariants' => $selectedVariants,
            'variantOptions' => $variantOptions,
            'flag' => $flag,
            'maxProductPrice' => $maxProductPrice,
        ]);
    }
}
