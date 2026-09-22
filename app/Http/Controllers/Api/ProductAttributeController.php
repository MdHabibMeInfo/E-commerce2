<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductAttributeRequest;
use App\Http\Requests\UpdateProductAttributeRequest;
use App\Http\Resources\ProductAttributeResource;
use App\Models\ProductAttribute;
use App\Services\ProductAttributeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductAttributeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize(
            'viewAny',
            ProductAttribute::class
        );

        $query = ProductAttribute::query();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query->where(
                    'name',
                    'like',
                    "%{$search}%"
                )->orWhere(
                    'slug',
                    'like',
                    "%{$search}%"
                );
            });
        }

        if ($request->has('is_active')) {
            $query->where(
                'is_active',
                $request->boolean('is_active')
            );
        }

        $perPage = min(
            max((int) $request->input('per_page', 20), 1),
            100
        );

        $attributes = $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Product attributes retrieved successfully.',
            'attributes' => ProductAttributeResource::collection(
                $attributes->items()
            ),
            'pagination' => [
                'current_page' => $attributes->currentPage(),
                'per_page' => $attributes->perPage(),
                'total' => $attributes->total(),
                'last_page' => $attributes->lastPage(),
                'from' => $attributes->firstItem(),
                'to' => $attributes->lastItem(),
            ],
        ]);
    }

    public function show(
        ProductAttribute $productAttribute
    ): JsonResponse {
        $this->authorize(
            'view',
            $productAttribute
        );

        return response()->json([
            'message' => 'Product attribute retrieved successfully.',
            'attribute' => new ProductAttributeResource(
                $productAttribute
            ),
        ]);
    }

    public function store(
        StoreProductAttributeRequest $request,
        ProductAttributeService $productAttributeService
    ): JsonResponse {
        $this->authorize(
            'create',
            ProductAttribute::class
        );

        $productAttribute = $productAttributeService->create(
            $request->validated()
        );

        return response()->json([
            'message' => 'Product attribute created successfully.',
            'attribute' => new ProductAttributeResource(
                $productAttribute
            ),
        ], 201);
    }

    public function update(
        UpdateProductAttributeRequest $request,
        ProductAttribute $productAttribute,
        ProductAttributeService $productAttributeService
    ): JsonResponse {
        $this->authorize(
            'update',
            $productAttribute
        );

        $productAttribute = $productAttributeService->update(
            $productAttribute,
            $request->validated()
        );

        return response()->json([
            'message' => 'Product attribute updated successfully.',
            'attribute' => new ProductAttributeResource(
                $productAttribute
            ),
        ]);
    }

    public function destroy(
        ProductAttribute $productAttribute,
        ProductAttributeService $productAttributeService
    ): JsonResponse {
        $this->authorize(
            'delete',
            $productAttribute
        );

        $productAttributeService->delete(
            $productAttribute
        );

        return response()->json([
            'message' => 'Product attribute deleted successfully.',
        ]);
    }

    public function restore(
        ProductAttribute $productAttribute,
        ProductAttributeService $productAttributeService
    ): JsonResponse {
        $this->authorize(
            'restore',
            $productAttribute
        );

        $productAttribute = $productAttributeService->restore(
            $productAttribute
        );

        return response()->json([
            'message' => 'Product attribute restored successfully.',
            'attribute' => new ProductAttributeResource(
                $productAttribute
            ),
        ]);
    }
}
