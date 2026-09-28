<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderProductImagesRequest;
use App\Http\Requests\StoreProductImageRequest;
use App\Http\Requests\UpdateProductImageRequest;
use App\Http\Resources\ProductImageResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ProductImageService;
use Illuminate\Http\JsonResponse;

class ProductImageController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        $this->authorize('view', $product);
        $this->authorize('viewAny', ProductImage::class);

        $images = $product->images()->get();

        return response()->json([
            'message' => 'Product images retrieved successfully.',
            'images' => ProductImageResource::collection($images),
        ]);
    }

    public function show(
        Product $product,
        ProductImage $productImage
    ): ProductImageResource {
        $this->authorize('view', $product);
        $this->authorize('view', $productImage);

        return new ProductImageResource($productImage);
    }

    public function store(
        StoreProductImageRequest $request,
        Product $product,
        ProductImageService $imageService
    ): JsonResponse {
        $this->authorize('update', $product);
        $this->authorize('create', ProductImage::class);

        if ($request->hasFile('images')) {
            $images = $imageService->createMultipleImages(
                $product,
                $request->file('images'),
                $request->filled('primary_index') ? (int) $request->input('primary_index') : null,
                $request->input('alt_texts', [])
            );

            return response()->json([
                'message' => 'Product images uploaded successfully.',
                'images' => ProductImageResource::collection($images),
            ], 201);
        }

        $productImage = $imageService->createImage(
            $product,
            $request->validated()
        );

        return response()->json([
            'message' => 'Product image uploaded successfully.',
            'image' => new ProductImageResource($productImage),
        ], 201);
    }

    public function update(
        UpdateProductImageRequest $request,
        Product $product,
        ProductImage $productImage,
        ProductImageService $imageService
    ): JsonResponse {
        $this->authorize('update', $product);
        $this->authorize('update', $productImage);

        $productImage = $imageService->updateImage(
            $productImage,
            $request->validated()
        );

        return response()->json([
            'message' => 'Product image updated successfully.',
            'image' => new ProductImageResource($productImage),
        ]);
    }

    public function destroy(
        Product $product,
        ProductImage $productImage,
        ProductImageService $imageService
    ): JsonResponse {
        $this->authorize('update', $product);
        $this->authorize('delete', $productImage);

        $imageService->deleteImage($productImage);

        return response()->json([
            'message' => 'Product image deleted successfully.',
        ]);
    }

    public function setPrimary(
        Product $product,
        ProductImage $productImage,
        ProductImageService $imageService
    ): JsonResponse {
        $this->authorize('update', $product);
        $this->authorize('update', $productImage);

        $productImage = $imageService->setPrimaryImage($productImage);

        return response()->json([
            'message' => 'Product primary image set successfully.',
            'image' => new ProductImageResource($productImage),
        ]);
    }

    public function reorder(
        ReorderProductImagesRequest $request,
        Product $product,
        ProductImageService $imageService
    ): JsonResponse {
        $this->authorize('update', $product);
        $this->authorize('update', ProductImage::class);

        $imageService->reorderImages(
            $product,
            $request->validated('image_ids')
        );

        return response()->json([
            'message' => 'Product images reordered successfully.',
            'images' => ProductImageResource::collection($product->images()->get()),
        ]);
    }
}
