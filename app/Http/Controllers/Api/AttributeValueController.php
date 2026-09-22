<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttributeValueRequest;
use App\Http\Requests\UpdateAttributeValueRequest;
use App\Http\Resources\AttributeValueResource;
use App\Models\AttributeValue;
use App\Services\AttributeValueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttributeValueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize(
            'viewAny',
            AttributeValue::class
        );

        $query = AttributeValue::query()
            ->with('productAttribute');

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = $request
                ->string('search')
                ->toString();

            $query->where(function ($query) use ($search) {
                $query->where(
                    'value',
                    'like',
                    "%{$search}%"
                )->orWhere(
                    'slug',
                    'like',
                    "%{$search}%"
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Filter by Product Attribute
        |--------------------------------------------------------------------------
        */

        if ($request->has('product_attribute_id')) {
            $query->where(
                'product_attribute_id',
                $request->input('product_attribute_id')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter by active status
        |--------------------------------------------------------------------------
        */

        if ($request->has('is_active')) {
            $query->where(
                'is_active',
                $request->boolean('is_active')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $perPage = min(
            max(
                (int) $request->input('per_page', 20),
                1
            ),
            100
        );

        $attributeValues = $query
            ->orderBy('sort_order')
            ->orderBy('value')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Attribute values retrieved successfully.',

            'attribute_values' =>
            AttributeValueResource::collection(
                $attributeValues->items()
            ),

            'pagination' => [
                'current_page' => $attributeValues->currentPage(),
                'per_page' => $attributeValues->perPage(),
                'total' => $attributeValues->total(),
                'last_page' => $attributeValues->lastPage(),
                'from' => $attributeValues->firstItem(),
                'to' => $attributeValues->lastItem(),
            ],
        ]);
    }

    public function show(
        AttributeValue $attributeValue
    ): JsonResponse {
        $this->authorize(
            'view',
            $attributeValue
        );

        $attributeValue->load(
            'productAttribute'
        );

        return response()->json([
            'message' => 'Attribute value retrieved successfully.',

            'attribute_value' =>
            new AttributeValueResource(
                $attributeValue
            ),
        ]);
    }

    public function store(
        StoreAttributeValueRequest $request,
        AttributeValueService $attributeValueService
    ): JsonResponse {
        $this->authorize(
            'create',
            AttributeValue::class
        );

        $attributeValue =
            $attributeValueService->create(
                $request->validated()
            );

        $attributeValue->load(
            'productAttribute'
        );

        return response()->json([
            'message' => 'Attribute value created successfully.',

            'attribute_value' =>
            new AttributeValueResource(
                $attributeValue
            ),
        ], 201);
    }

    public function update(
        UpdateAttributeValueRequest $request,
        AttributeValue $attributeValue,
        AttributeValueService $attributeValueService
    ): JsonResponse {
        $this->authorize(
            'update',
            $attributeValue
        );

        $attributeValue =
            $attributeValueService->update(
                $attributeValue,
                $request->validated()
            );

        $attributeValue->load(
            'productAttribute'
        );

        return response()->json([
            'message' => 'Attribute value updated successfully.',

            'attribute_value' =>
            new AttributeValueResource(
                $attributeValue
            ),
        ]);
    }

    public function destroy(
        AttributeValue $attributeValue,
        AttributeValueService $attributeValueService
    ): JsonResponse {
        $this->authorize(
            'delete',
            $attributeValue
        );

        $attributeValueService->delete(
            $attributeValue
        );

        return response()->json([
            'message' => 'Attribute value deleted successfully.',
        ]);
    }

    public function restore(
        AttributeValue $attributeValue,
        AttributeValueService $attributeValueService
    ): JsonResponse {
        $this->authorize(
            'restore',
            $attributeValue
        );

        $attributeValue =
            $attributeValueService->restore(
                $attributeValue
            );

        $attributeValue->load(
            'productAttribute'
        );

        return response()->json([
            'message' => 'Attribute value restored successfully.',

            'attribute_value' =>
            new AttributeValueResource(
                $attributeValue
            ),
        ]);
    }
}
