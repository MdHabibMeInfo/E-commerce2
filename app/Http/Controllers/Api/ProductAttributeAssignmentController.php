<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignProductAttributesRequest;
use App\Http\Resources\ProductAttributeResource;
use App\Models\Product;
use App\Services\ProductAttributeAssignmentService;
use Illuminate\Http\JsonResponse;

class ProductAttributeAssignmentController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        $product->load('productAttributes');

        return response()->json([
            'message' => 'Product attributes retrieved successfully.',
            'attributes' => ProductAttributeResource::collection(
                $product->productAttributes
            ),
        ]);
    }

    public function update(
        AssignProductAttributesRequest $request,
        Product $product,
        ProductAttributeAssignmentService $assignmentService
    ): JsonResponse {
        $this->authorize('update', $product);

        $product = $assignmentService->assign(
            $product,
            $request->validated('product_attribute_ids')
        );

        return response()->json([
            'message' => 'Product attributes updated successfully.',
            'attributes' => ProductAttributeResource::collection(
                $product->productAttributes
            ),
        ]);
    }
}
