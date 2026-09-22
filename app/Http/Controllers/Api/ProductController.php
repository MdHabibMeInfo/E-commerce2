<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{

    public function index(
        ProductIndexRequest $request
    ): JsonResponse {
        $this->authorize('viewAny', Product::class);

        $validated = $request->validated();

        $query = Product::query()
            ->with('category');

        if (isset($validated['search'])) {
            $search = $validated['search'];

            $query->where(function ($query) use ($search) {
                $query->where(
                    'name',
                    'like',
                    "%{$search}%"
                )->orWhere(
                    'sku',
                    'like',
                    "%{$search}%"
                )->orWhere(
                    'slug',
                    'like',
                    "%{$search}%"
                );
            });
        }

        if (isset($validated['category_id'])) {
            $query->where(
                'category_id',
                $validated['category_id']
            );
        }

        if (isset($validated['is_active'])) {
            $query->where(
                'is_active',
                $validated['is_active']
            );
        }

        if (isset($validated['type'])) {
            $query->where(
                'type',
                $validated['type']
            );
        }

        if (isset($validated['min_price'])) {
            $query->where(
                'price',
                '>=',
                $validated['min_price']
            );
        }

        if (isset($validated['max_price'])) {
            $query->where(
                'price',
                '<=',
                $validated['max_price']
            );
        }

        $sort = $validated['sort'] ?? 'id';
        $direction = $validated['direction'] ?? 'desc';
        $perPage = $validated['per_page'] ?? 20;

        $products = $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Products retrieved successfully.',

            'products' => ProductResource::collection(
                $products->items()
            ),

            'pagination' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem(),
            ],
        ]);
    }

    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        $product->load('category');

        return response()->json([
            'message' => 'Product retrieved successfully.',

            'product' => new ProductResource($product),
        ]);
    }

    public function store(
        StoreProductRequest $request,
        ProductService $productService
    ): JsonResponse {
        $this->authorize('create', Product::class);

        $product = $productService->create(
            $request->validated()
        );

        $product->load('category');

        return response()->json([
            'message' => 'Product created successfully.',
            'product' => new ProductResource($product),
        ], 201);
    }

    public function update(
        UpdateProductRequest $request,
        Product $product,
        ProductService $productService
    ): JsonResponse {
        $this->authorize('update', $product);

        $product = $productService->update(
            $product,
            $request->validated()
        );

        $product->load('category');

        return response()->json([
            'message' => 'Product updated successfully.',

            'product' => new ProductResource($product),
        ]);
    }

    public function destroy(
        Product $product,
        ProductService $productService
    ): JsonResponse {
        $this->authorize('delete', $product);

        $productService->delete($product);

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }

    public function restore(
        Product $product,
        ProductService $productService
    ): JsonResponse {
        $this->authorize('restore', $product);

        $product = $productService->restore($product);

        $product->load('category');

        return response()->json([
            'message' => 'Product restored successfully.',

            'product' => new ProductResource($product),
        ]);
    }
}
