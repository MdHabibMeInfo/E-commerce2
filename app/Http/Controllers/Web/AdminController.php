<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\DuplicateVariantCombinationException;
use App\Exceptions\InvalidVariantAttributeValueException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\StoreProductVariantRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Requests\UpdateProductVariantRequest;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\AttributeValueService;
use App\Services\CategoryService;
use App\Services\ProductAttributeAssignmentService;
use App\Services\ProductAttributeService;
use App\Services\ProductImageService;
use App\Services\ProductService;
use App\Services\ProductVariantService;
use App\Services\SecurityEventService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    private const ITEMS_PER_PAGE = 25;

    public function index(): View
    {
        $currentUser = auth()->user();
        $roleNames = $currentUser ? $currentUser->getRoleNames()->toArray() : ['System Owner'];
        $permissions = $currentUser ? $currentUser->getPermissionNames()->toArray() : [
            'products.manage',
            'orders.manage',
            'users.manage',
            'settings.manage',
            'security.manage',
        ];

        $stats = [
            'products' => Product::query()->count(),
            'orders' => Order::query()->count(),
            'users' => User::query()->count(),
            'revenue' => Order::query()->sum('total') ?? 0,
        ];

        return view('admin.index', compact('currentUser', 'roleNames', 'permissions', 'stats'));
    }

    public function categoriesIndex(Request $request): View
    {
        $query = Category::query()->with('parent');
        $search = $request->string('search')->trim()->toString();

        if ($search !== '') {
            $this->applySearch($query, ['name', 'slug'], $search);
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        if ($request->input('parent_id') === 'root') {
            $query->whereNull('parent_id');
        } elseif ($request->filled('parent_id') && ctype_digit((string) $request->input('parent_id'))) {
            $query->where('parent_id', (int) $request->input('parent_id'));
        }

        $categories = $query->latest()->paginate(self::ITEMS_PER_PAGE)->withQueryString();
        $selectedParentCategory = $request->input('parent_id') === 'root'
            ? null
            : (ctype_digit((string) $request->input('parent_id'))
                ? Category::query()->find((int) $request->input('parent_id'))
                : null);

        return view('admin.categories.index', compact('categories', 'selectedParentCategory'));
    }

    public function categorySuggestions(Request $request): JsonResponse
    {
        $search = trim((string) ($request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ])['search'] ?? ''));

        $categories = Category::query();

        if ($request->boolean('trashed_products')) {
            $categories->withTrashed()->whereHas('products', function (Builder $products): void {
                $products->onlyTrashed();
            });
        } elseif ($request->boolean('trashed')) {
            $categories->onlyTrashed();
        }

        if ($request->boolean('parents_only')) {
            $categories->whereHas('children');
        }

        if ($search !== '') {
            $this->applySearch($categories, ['name', 'slug'], $search);
        }

        if ($search !== '') {
            $lowerSearch = strtolower($search);
            $categories->orderByRaw(
                'CASE WHEN LOWER(name) LIKE ? THEN 0 WHEN LOWER(name) LIKE ? THEN 1 ELSE 2 END',
                [$lowerSearch.'%', '%'.$lowerSearch.'%']
            );
        }

        $matches = $categories
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'slug'])
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'label' => $category->name,
                'detail' => $category->slug,
                'value' => $category->name,
            ])
            ->all();

        return response()->json($matches);
    }

    public function createCategory(): View
    {
        $categories = Category::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        return view('admin.categories.create', compact('categories'));
    }

    public function showCategory(Category $category): View
    {
        $category->load([
            'parent',
            'children' => function ($query) {
                $query->orderBy('sort_order')->orderBy('name');
            },
            'products' => function ($query) {
                $query->latest()->limit(10);
            },
        ]);

        return view('admin.categories.show', compact('category'));
    }

    public function editCategory(Category $category): View
    {
        $categories = Category::query()
            ->whereNull('deleted_at')
            ->whereKeyNot($category->id)
            ->orderBy('name')
            ->get();

        return view('admin.categories.edit', compact('category', 'categories'));
    }

    public function trashedCategories(Request $request): View
    {
        $query = Category::query()
            ->onlyTrashed()
            ->with('parent');
        $search = $request->string('search')->trim()->toString();

        if ($search !== '') {
            $this->applySearch($query, ['name', 'slug'], $search);
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        if ($request->input('parent_id') === 'root') {
            $query->whereNull('parent_id');
        } elseif ($request->filled('parent_id') && ctype_digit((string) $request->input('parent_id'))) {
            $query->where('parent_id', (int) $request->input('parent_id'));
        }

        $categories = $query->latest('deleted_at')->paginate(self::ITEMS_PER_PAGE)->withQueryString();
        $selectedParentCategory = $request->input('parent_id') === 'root'
            ? null
            : (ctype_digit((string) $request->input('parent_id'))
                ? Category::query()->find((int) $request->input('parent_id'))
                : null);

        return view('admin.categories.trashed', compact('categories', 'selectedParentCategory'));
    }

    public function attributesIndex(Request $request): View
    {
        $query = ProductAttribute::query();
        $search = $request->string('search')->trim()->toString();

        if ($search !== '') {
            $this->applySearch($query, ['name', 'slug'], $search);
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $attributes = $query->latest()->paginate(self::ITEMS_PER_PAGE)->withQueryString();

        return view('admin.attributes.index', compact('attributes'));
    }

    public function attributeSuggestions(Request $request): JsonResponse
    {
        $search = trim((string) ($request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ])['search'] ?? ''));

        $attributes = ProductAttribute::query();

        if ($request->boolean('trashed')) {
            $attributes->onlyTrashed();
        }

        if ($search !== '') {
            $this->applySearch($attributes, ['name', 'slug'], $search);
        }

        $matches = $attributes
            ->orderBy('name')
            ->get(['name', 'slug'])
            ->map(fn (ProductAttribute $attribute): array => [
                'label' => $attribute->name,
                'detail' => $attribute->slug,
                'value' => $attribute->name,
            ])
            ->all();

        if ($search !== '') {
            $lowerSearch = strtolower($search);
            usort($matches, function (array $a, array $b) use ($lowerSearch): int {
                $aPriority = str_starts_with(strtolower((string) $a['label']), $lowerSearch)
                    ? 0
                    : (str_contains(strtolower((string) $a['label']), $lowerSearch) ? 1 : 2);
                $bPriority = str_starts_with(strtolower((string) $b['label']), $lowerSearch)
                    ? 0
                    : (str_contains(strtolower((string) $b['label']), $lowerSearch) ? 1 : 2);

                return $aPriority <=> $bPriority ?: strcmp(strtolower((string) $a['label']), strtolower((string) $b['label']));
            });
        }

        return response()->json(array_slice($matches, 0, 8));
    }

    public function createAttribute(): View
    {
        return view('admin.attributes.create');
    }

    public function showAttribute(ProductAttribute $attribute): View
    {
        $attribute->load([
            'values' => function ($query) {
                $query->withTrashed()->orderBy('sort_order')->orderBy('id');
            },
        ]);

        return view('admin.attributes.show', compact('attribute'));
    }

    public function editAttribute(ProductAttribute $attribute): View
    {
        return view('admin.attributes.edit', compact('attribute'));
    }

    public function trashedAttributes(Request $request): View
    {
        $query = ProductAttribute::query()->onlyTrashed();
        $search = $request->string('search')->trim()->toString();

        if ($search !== '') {
            $this->applySearch($query, ['name', 'slug'], $search);
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $attributes = $query->latest('deleted_at')->paginate(self::ITEMS_PER_PAGE)->withQueryString();

        return view('admin.attributes.trashed', compact('attributes'));
    }

    public function createProduct(): View
    {
        $categories = Category::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        return view('admin.products.create', compact('categories'));
    }

    public function editProduct(Product $product): View
    {
        $categories = Category::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function productsIndex(Request $request): View
    {
        $query = Product::query()->with(['category', 'primaryImage', 'images']);
        $search = $request->string('search')->trim()->toString();

        if ($search !== '') {
            $this->applySearch($query, ['name', 'slug', 'sku'], $search);
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        if ($request->filled('category_id') && ctype_digit((string) $request->input('category_id'))) {
            $query->where('category_id', (int) $request->input('category_id'));
        }

        if (in_array($request->input('type'), ['simple', 'variable'], true)) {
            $query->where('type', $request->input('type'));
        }

        $products = $query->latest()->paginate(self::ITEMS_PER_PAGE)->withQueryString();
        $selectedCategory = $request->filled('category_id') && ctype_digit((string) $request->input('category_id'))
            ? Category::query()->find((int) $request->input('category_id'))
            : null;

        return view('admin.products.index', compact('products', 'selectedCategory'));
    }

    public function productSuggestions(Request $request): JsonResponse
    {
        $search = trim((string) ($request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ])['search'] ?? ''));

        $products = Product::query();

        if ($request->boolean('trashed')) {
            $products->onlyTrashed();
        }

        if ($search !== '') {
            $this->applySearch($products, ['name', 'slug', 'sku'], $search);
        }

        if ($search !== '') {
            $lowerSearch = strtolower($search);
            $products->orderByRaw(
                'CASE WHEN LOWER(name) LIKE ? THEN 0 WHEN LOWER(name) LIKE ? THEN 1 ELSE 2 END',
                [$lowerSearch.'%', '%'.$lowerSearch.'%']
            );
        }

        $matches = $products
            ->orderBy('name')
            ->limit(8)
            ->get(['name', 'sku'])
            ->map(fn (Product $product): array => [
                'label' => $product->name,
                'detail' => $product->sku,
                'value' => $product->name,
            ])
            ->all();

        return response()->json($matches);
    }

    private function applySearch(Builder $query, array $columns, string $search): Builder
    {
        $normalizedSearch = trim($search);

        if ($normalizedSearch === '') {
            return $query;
        }

        $lowerSearch = strtolower($normalizedSearch);

        return $query->where(function (Builder $query) use ($columns, $lowerSearch): void {
            foreach ($columns as $column) {
                $query->orWhereRaw('LOWER('.$column.') LIKE ?', [$lowerSearch.'%'])
                    ->orWhereRaw('LOWER('.$column.') LIKE ?', ['%'.$lowerSearch.'%']);
            }
        });
    }

    public function showProduct(Product $product): View
    {
        $product->load([
            'category:id,name,slug',
            'images' => function ($query) {
                $query
                    ->orderByDesc('is_primary')
                    ->orderBy('sort_order')
                    ->orderBy('id');
            },
            'productAttributes.values' => function ($query) {
                $query->withTrashed()->orderBy('sort_order')->orderBy('id');
            },
        ]);

        $availableAttributes = ProductAttribute::query()
            ->where('is_active', true)
            ->with(['values' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $variants = $product->variants()
            ->withTrashed()
            ->with('attributeValues.productAttribute')
            ->orderByDesc('id')
            ->paginate(self::ITEMS_PER_PAGE)
            ->withQueryString();
        $canCreateVariants = $product->productAttributes->isNotEmpty()
            && $product->productAttributes->every(
                fn (ProductAttribute $attribute): bool => $attribute->values->contains(
                    fn (AttributeValue $value): bool => ! $value->trashed() && $value->is_active
                )
            );

        return view('admin.products.show', compact('product', 'availableAttributes', 'variants', 'canCreateVariants'));
    }

    public function updateProductAttributes(
        Request $request,
        Product $product,
        ProductAttributeAssignmentService $assignmentService
    ): RedirectResponse {
        $validated = $request->validate([
            'product_attribute_ids' => ['nullable', 'array'],
            'product_attribute_ids.*' => ['required', 'integer', 'distinct', Rule::exists('product_attributes', 'id')->where('is_active', true)],
        ]);

        $assignmentService->assign($product, $validated['product_attribute_ids'] ?? []);

        return redirect()->route('admin.products.show', $product)->with('success', 'Product attributes updated successfully.');
    }

    public function trashedProducts(Request $request): View
    {
        $query = Product::query()
            ->onlyTrashed()
            ->with([
                'category' => function (BelongsTo $category): void {
                    $category->withTrashed();
                },
                'primaryImage',
                'images',
            ]);
        $search = $request->string('search')->trim()->toString();

        if ($search !== '') {
            $this->applySearch($query, ['name', 'slug', 'sku'], $search);
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        if ($request->filled('category_id') && ctype_digit((string) $request->input('category_id'))) {
            $query->where('category_id', (int) $request->input('category_id'));
        }

        if (in_array($request->input('type'), ['simple', 'variable'], true)) {
            $query->where('type', $request->input('type'));
        }

        $products = $query->latest('deleted_at')->paginate(self::ITEMS_PER_PAGE)->withQueryString();
        $selectedCategory = $request->filled('category_id') && ctype_digit((string) $request->input('category_id'))
            ? Category::withTrashed()->find((int) $request->input('category_id'))
            : null;

        return view('admin.products.trashed', compact('products', 'selectedCategory'));
    }

    public function storeProduct(
        Request $request,
        ProductService $productService,
        ProductImageService $productImageService
    ): RedirectResponse {
        $validated = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:products,sku'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:simple,variable'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0', 'gt:price'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
            'primary_index' => ['nullable', 'integer', 'min:0'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active', true);

        $product = $productService->create($validated);

        if ($request->hasFile('images')) {
            $productImageService->createMultipleImages(
                $product,
                $request->file('images'),
                $request->filled('primary_index') ? (int) $request->input('primary_index') : null
            );
        } elseif ($request->hasFile('image')) {
            $productImageService->createImage($product, [
                'image' => $request->file('image'),
                'alt_text' => $validated['alt_text'] ?? null,
                'sort_order' => 0,
                'is_primary' => $request->boolean('is_primary', true),
            ]);
        }

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    public function updateProduct(
        Request $request,
        Product $product,
        ProductService $productService,
        ProductImageService $productImageService
    ): RedirectResponse {
        $validated = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:simple,variable'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
            'primary_index' => ['nullable', 'integer', 'min:0'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($request->filled('compare_at_price') && $request->input('compare_at_price') <= ($request->input('price') ?? $product->price)) {
            return back()->withErrors(['compare_at_price' => 'Compare at price must be greater than price.'])->withInput();
        }

        $productService->update($product, $validated);

        if ($request->hasFile('images')) {
            $productImageService->createMultipleImages(
                $product->fresh(),
                $request->file('images'),
                $request->filled('primary_index') ? (int) $request->input('primary_index') : null
            );
        } elseif ($request->hasFile('image')) {
            $productImageService->createImage($product->fresh(), [
                'image' => $request->file('image'),
                'alt_text' => $validated['alt_text'] ?? null,
                'sort_order' => 0,
                'is_primary' => $request->boolean('is_primary', true),
            ]);
        }

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    public function updateProductAvailability(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'is_out_of_stock' => ['required', 'boolean'],
        ]);

        $product->forceFill([
            'is_out_of_stock' => $validated['is_out_of_stock'],
        ])->save();

        return redirect()->route('admin.products.index')->with(
            'success',
            $product->is_out_of_stock ? 'Product marked out of stock.' : 'Product marked available.'
        );
    }

    public function uploadProductImages(
        Request $request,
        Product $product,
        ProductImageService $productImageService
    ): RedirectResponse {
        $request->validate([
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:5120'],
        ]);

        $productImageService->createMultipleImages(
            $product,
            $request->file('images')
        );

        return back()->with('success', 'Product images uploaded successfully.');
    }

    public function setPrimaryProductImage(
        Product $product,
        ProductImage $image,
        ProductImageService $productImageService
    ): RedirectResponse {
        abort_if($image->product_id !== $product->id, 404);

        $productImageService->setPrimaryImage($image);

        return back()->with('success', 'Primary image updated successfully.');
    }

    public function destroyProductImage(
        Product $product,
        ProductImage $image,
        ProductImageService $productImageService
    ): RedirectResponse {
        abort_if($image->product_id !== $product->id, 404);

        $productImageService->deleteImage($image);

        return back()->with('success', 'Product image deleted successfully.');
    }

    public function deleteProduct(Product $product, ProductService $productService): RedirectResponse
    {
        $productService->delete($product);

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }

    public function destroyProduct(Product $product, ProductService $productService): RedirectResponse
    {
        return $this->deleteProduct($product, $productService);
    }

    public function restoreProduct(string $productId, ProductService $productService): RedirectResponse
    {
        $product = Product::withTrashed()->findOrFail($productId);
        $productService->restore($product);

        return redirect()->route('admin.products.index')->with('success', 'Product restored successfully.');
    }

    public function storeCategory(StoreCategoryRequest $request, CategoryService $categoryService): RedirectResponse
    {
        $validated = $request->validated();

        $validated['slug'] = trim((string) ($validated['slug'] ?? '')) !== '' ? $validated['slug'] : null;
        $validated['is_active'] = $request->boolean('is_active', true);

        $categoryService->create($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Category created successfully.');
    }

    public function updateCategory(UpdateCategoryRequest $request, Category $category, CategoryService $categoryService): RedirectResponse
    {
        $validated = $request->validated();

        $validated['slug'] = trim((string) ($validated['slug'] ?? '')) !== '' ? $validated['slug'] : null;
        $validated['is_active'] = $request->boolean('is_active', $category->is_active);

        $categoryService->update($category, $validated);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully.');
    }

    public function deleteCategory(Category $category, CategoryService $categoryService): RedirectResponse
    {
        $categoryService->delete($category);

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted successfully.');
    }

    public function destroyCategory(Category $category, CategoryService $categoryService): RedirectResponse
    {
        return $this->deleteCategory($category, $categoryService);
    }

    public function restoreCategory(string $categoryId, CategoryService $categoryService): RedirectResponse
    {
        $category = Category::withTrashed()->findOrFail($categoryId);
        $categoryService->restore($category);

        return redirect()->route('admin.categories.index')->with('success', 'Category restored successfully.');
    }

    public function storeAttribute(Request $request, ProductAttributeService $productAttributeService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['slug'] = trim((string) ($validated['slug'] ?? '')) !== '' ? $validated['slug'] : null;
        $validated['is_active'] = $request->boolean('is_active', true);

        $productAttributeService->create($validated);

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute created successfully.');
    }

    public function updateAttribute(Request $request, ProductAttribute $attribute, ProductAttributeService $productAttributeService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['slug'] = trim((string) ($validated['slug'] ?? '')) !== '' ? $validated['slug'] : null;
        $validated['is_active'] = $request->boolean('is_active', $attribute->is_active);

        $productAttributeService->update($attribute, $validated);

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute updated successfully.');
    }

    public function deleteAttribute(ProductAttribute $attribute, ProductAttributeService $productAttributeService): RedirectResponse
    {
        $productAttributeService->delete($attribute);

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute deleted successfully.');
    }

    public function destroyAttribute(ProductAttribute $attribute, ProductAttributeService $productAttributeService): RedirectResponse
    {
        return $this->deleteAttribute($attribute, $productAttributeService);
    }

    public function restoreAttribute(string $attributeId, ProductAttributeService $productAttributeService): RedirectResponse
    {
        $attribute = ProductAttribute::withTrashed()->findOrFail($attributeId);
        $productAttributeService->restore($attribute);

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute restored successfully.');
    }

    public function storeAttributeValue(Request $request, ProductAttribute $attribute, AttributeValueService $attributeValueService): RedirectResponse
    {
        $validated = $request->validate([
            'value' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['product_attribute_id'] = $attribute->id;
        $attributeValueService->create($validated);

        return redirect()->route('admin.attributes.show', $attribute)->with('success', 'Attribute value created successfully.');
    }

    public function updateAttributeValue(Request $request, ProductAttribute $attribute, AttributeValue $attributeValue, AttributeValueService $attributeValueService): RedirectResponse
    {
        abort_if($attributeValue->product_attribute_id !== $attribute->id, 404);

        $validated = $request->validate([
            'value' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['product_attribute_id'] = $attribute->id;
        $attributeValueService->update($attributeValue, $validated);

        return redirect()->route('admin.attributes.show', $attribute)->with('success', 'Attribute value updated successfully.');
    }

    public function deleteAttributeValue(ProductAttribute $attribute, AttributeValue $attributeValue, AttributeValueService $attributeValueService): RedirectResponse
    {
        abort_if($attributeValue->product_attribute_id !== $attribute->id, 404);

        $attributeValueService->delete($attributeValue);

        return redirect()->route('admin.attributes.show', $attribute)->with('success', 'Attribute value deleted successfully.');
    }

    public function restoreAttributeValue(ProductAttribute $attribute, AttributeValue $attributeValue, AttributeValueService $attributeValueService): RedirectResponse
    {
        abort_if($attributeValue->product_attribute_id !== $attribute->id, 404);

        $attributeValueService->restore($attributeValue);

        return redirect()->route('admin.attributes.show', $attribute)->with('success', 'Attribute value restored successfully.');
    }

    public function storeVariant(StoreProductVariantRequest $request, Product $product, ProductVariantService $productVariantService): RedirectResponse
    {
        try {
            $productVariantService->createVariant($product, $request->validated());
        } catch (DuplicateVariantCombinationException|InvalidVariantAttributeValueException $exception) {
            return back()
                ->withErrors(['attribute_value_ids' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()->route('admin.products.show', $product)->with('success', 'Variant created successfully.');
    }

    public function updateVariant(UpdateProductVariantRequest $request, Product $product, ProductVariant $variant, ProductVariantService $productVariantService): RedirectResponse
    {
        try {
            $productVariantService->updateVariant($product, $variant, $request->validated());
        } catch (DuplicateVariantCombinationException|InvalidVariantAttributeValueException $exception) {
            return back()
                ->withErrors(['attribute_value_ids' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()->route('admin.products.show', $product)->with('success', 'Variant updated successfully.');
    }

    public function deleteVariant(Product $product, ProductVariant $variant, ProductVariantService $productVariantService): RedirectResponse
    {
        abort_if($variant->product_id !== $product->id, 404);
        $productVariantService->deleteVariant($variant);

        return redirect()->route('admin.products.show', $product)->with('success', 'Variant deleted successfully.');
    }

    public function restoreVariant(Product $product, ProductVariant $variant, ProductVariantService $productVariantService): RedirectResponse
    {
        try {
            $productVariantService->restoreVariant($product, $variant);
        } catch (DuplicateVariantCombinationException|InvalidVariantAttributeValueException $exception) {
            return back()->withErrors(['variant' => $exception->getMessage()]);
        }

        return redirect()->route('admin.products.show', $product)->with('success', 'Variant restored successfully.');
    }

    public function profile(): View
    {
        $profileUser = auth()->user();

        return view('admin.profile', [
            'profileUser' => $profileUser,
            'profilePhotoUrl' => $profileUser?->profile_photo_path
                ? Storage::disk('public')->url($profileUser->profile_photo_path)
                : null,
        ]);
    }

    public function updateProfile(Request $request, SecurityEventService $events): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $email = $request->input('email');

        if (is_string($email)) {
            $request->merge(['email' => Str::lower(trim($email))]);
        }
        if ($request->input('email') !== $user->email) {
            return back()
                ->withErrors(['email' => 'Use Account Security to request a verified email change.'])
                ->withInput();
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->getKey())],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->getKey())],
            'current_password' => $request->input('email') !== $user->email
                ? ['required', 'current_password']
                : ['nullable', 'current_password'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $oldProfilePhotoPath = $user->profile_photo_path;
        $newProfilePhotoPath = $request->file('profile_photo')?->store('profile-photos', 'public');

        $user->fill([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
        ]);

        if ($newProfilePhotoPath) {
            $user->profile_photo_path = $newProfilePhotoPath;
        }

        $user->save();

        if ($newProfilePhotoPath && $oldProfilePhotoPath) {
            Storage::disk('public')->delete($oldProfilePhotoPath);
        }

        $events->record($request, $user, 'profile_updated', [
            'fields' => ['name', 'phone', 'profile_photo'],
        ]);

        return redirect()->route('admin.profile')->with('status', 'Profile details updated.');
    }

    public function deleteProfilePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->profile_photo_path = null;
            $user->save();
        }

        return redirect()->route('admin.profile')->with('status', 'Profile photo removed.');
    }

    public function notifications(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $notifications = Order::query()
            ->latest()
            ->limit(10)
            ->get(['id', 'order_number', 'customer_name', 'total', 'status', 'created_at']);
        $user->forceFill([
            'admin_notifications_last_seen_order_id' => (int) (Order::query()->max('id') ?? 0),
        ])->save();

        return view('admin.notifications', compact('notifications'));
    }

    public function unreadNotifications(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $lastSeenOrderId = $user->admin_notifications_last_seen_order_id;

        if ($lastSeenOrderId === null) {
            $lastSeenOrderId = (int) (Order::query()->max('id') ?? 0);
            $user->forceFill([
                'admin_notifications_last_seen_order_id' => $lastSeenOrderId,
            ])->save();
        }

        return response()->json([
            'count' => Order::query()
                ->where('id', '>', (int) $lastSeenOrderId)
                ->count(),
        ]);
    }

    public function securityEvents(): View
    {
        $securityEvents = SecurityEvent::query()
            ->management()
            ->with('user:id,name,email')
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('admin.security.events', compact('securityEvents'));
    }

    public function orders(): View
    {
        $orders = Order::query()
            ->with('items')
            ->latest()
            ->get();

        return view('admin.orders', compact('orders'));
    }
}
