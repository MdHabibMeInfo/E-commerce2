<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductVariantIndexRequest;
use App\Http\Requests\StoreProductVariantRequest;
use App\Http\Requests\UpdateProductVariantRequest;
use App\Http\Resources\ProductVariantResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductVariantService;
use Illuminate\Http\JsonResponse;

class ProductVariantController extends Controller
{
    public function index(
        ProductVariantIndexRequest $request,
        Product $product
    ): JsonResponse {
        $this->authorize('viewAny', ProductVariant::class);

        $validated = $request->validated();

        $query = $product->variants()
            ->with([
                'attributeValues.productAttribute',
            ]);

        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        if (isset($validated['search'])) {
            $search = $validated['search'];

            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if (isset($validated['is_active'])) {
            $query->where(
                'is_active',
                $validated['is_active']
            );
        }

        if (isset($validated['is_out_of_stock'])) {
            $query->where('is_out_of_stock', $validated['is_out_of_stock']);
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
        $direction = $validated['direction'] ?? 'asc';
        $perPage = $validated['per_page'] ?? 20;

        $variants = $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Product variants retrieved successfully.',

            'variants' => ProductVariantResource::collection(
                $variants->items()
            ),

            'pagination' => [
                'current_page' => $variants->currentPage(),
                'per_page' => $variants->perPage(),
                'total' => $variants->total(),
                'last_page' => $variants->lastPage(),
                'from' => $variants->firstItem(),
                'to' => $variants->lastItem(),
            ],
        ]);
    }

    public function show(
        Product $product,
        ProductVariant $variant
    ): JsonResponse {
        $this->authorize('view', $variant);

        $variant->load([
            'attributeValues.productAttribute',
        ]);

        return response()->json([
            'message' => 'Product variant retrieved successfully.',
            'variant' => new ProductVariantResource($variant),
        ]);
    }

    public function store(
        StoreProductVariantRequest $request,
        Product $product,
        ProductVariantService $variantService
    ): JsonResponse {
        $this->authorize('create', ProductVariant::class);

        $variant = $variantService->createVariant(
            $product,
            $request->validated()
        );

        $variant->load([
            'attributeValues.productAttribute',
        ]);

        return response()->json([
            'message' => 'Product variant created successfully.',
            'variant' => new ProductVariantResource($variant),
        ], 201);
    }

    public function update(
        UpdateProductVariantRequest $request,
        Product $product,
        ProductVariant $variant,
        ProductVariantService $variantService
    ): JsonResponse {
        $this->authorize('update', $variant);

        $variant = $variantService->updateVariant(
            $product,
            $variant,
            $request->validated()
        );

        $variant->load([
            'attributeValues.productAttribute',
        ]);

        return response()->json([
            'message' => 'Product variant updated successfully.',
            'variant' => new ProductVariantResource($variant),
        ]);
    }

    public function destroy(
        Product $product,
        ProductVariant $variant,
        ProductVariantService $variantService
    ): JsonResponse {
        $this->authorize('delete', $variant);

        $variantService->deleteVariant($variant);

        return response()->json([
            'message' => 'Product variant deleted successfully.',
        ]);
    }

    public function restore(
        Product $product,
        ProductVariant $variant,
        ProductVariantService $variantService
    ): JsonResponse {
        $this->authorize('restore', $variant);

        $variant = $variantService->restoreVariant($product, $variant);
        $variant->load('attributeValues.productAttribute');

        return response()->json([
            'message' => 'Product variant restored successfully.',
            'variant' => new ProductVariantResource($variant),
        ]);
    }
}
