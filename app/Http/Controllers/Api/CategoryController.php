<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        $query = Category::query()
            ->with('parent');

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

        if ($request->has('parent_id')) {
            $query->where(
                'parent_id',
                $request->input('parent_id')
            );
        }

        $perPage = min(
            max((int) $request->input('per_page', 20), 1),
            100
        );

        $categories = $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'message' => 'Categories retrieved successfully.',

            'categories' => CategoryResource::collection(
                $categories->items()
            ),

            'pagination' => [
                'current_page' => $categories->currentPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'last_page' => $categories->lastPage(),
                'from' => $categories->firstItem(),
                'to' => $categories->lastItem(),
            ],
        ]);
    }

    public function show(
        Category $category
    ): JsonResponse {
        $this->authorize('view', $category);

        $category->load('parent');

        return response()->json([
            'message' => 'Category retrieved successfully.',

            'category' => new CategoryResource($category),
        ]);
    }

    public function store(
        StoreCategoryRequest $request,
        CategoryService $categoryService
    ): JsonResponse {
        $this->authorize('create', Category::class);

        $category = $categoryService->create(
            $request->validated()
        );

        $category->load('parent');

        return response()->json([
            'message' => 'Category created successfully.',

            'category' => new CategoryResource($category),
        ], 201);
    }

    public function update(
        UpdateCategoryRequest $request,
        Category $category,
        CategoryService $categoryService
    ): JsonResponse {
        $this->authorize('update', $category);

        $category = $categoryService->update(
            $category,
            $request->validated()
        );

        $category->load('parent');

        return response()->json([
            'message' => 'Category updated successfully.',

            'category' => new CategoryResource($category),
        ]);
    }

    public function destroy(
        Category $category,
        CategoryService $categoryService
    ): JsonResponse {
        $this->authorize('delete', $category);

        $categoryService->delete($category);

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }

    public function restore(
        Category $category,
        CategoryService $categoryService
    ): JsonResponse {
        $this->authorize('restore', $category);

        $category = $categoryService->restore($category);

        $category->load('parent');

        return response()->json([
            'message' => 'Category restored successfully.',

            'category' => new CategoryResource($category),
        ]);
    }
}
