<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\AttributeValueService;
use App\Services\CategoryService;
use App\Services\ProductAttributeService;
use App\Services\ProductImageService;
use App\Services\ProductService;
use App\Services\ProductVariantService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminController extends Controller
{
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

    public function categoriesIndex(): View
    {
        $categories = Category::query()
            ->with('parent')
            ->latest()
            ->get();

        return view('admin.categories.index', compact('categories'));
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

    public function trashedCategories(): View
    {
        $categories = Category::query()
            ->onlyTrashed()
            ->with('parent')
            ->latest('deleted_at')
            ->get();

        return view('admin.categories.trashed', compact('categories'));
    }

    public function attributesIndex(): View
    {
        $attributes = ProductAttribute::query()
            ->latest()
            ->get();

        return view('admin.attributes.index', compact('attributes'));
    }

    public function createAttribute(): View
    {
        return view('admin.attributes.create');
    }

    public function showAttribute(ProductAttribute $attribute): View
    {
        $attribute->load([
            'values' => function ($query) {
                $query->orderBy('sort_order')->orderBy('id');
            },
        ]);

        return view('admin.attributes.show', compact('attribute'));
    }

    public function editAttribute(ProductAttribute $attribute): View
    {
        return view('admin.attributes.edit', compact('attribute'));
    }

    public function trashedAttributes(): View
    {
        $attributes = ProductAttribute::query()
            ->onlyTrashed()
            ->latest('deleted_at')
            ->get();

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

    public function productsIndex(): View
    {
        $products = Product::query()
            ->with(['category', 'primaryImage', 'images'])
            ->latest()
            ->get();

        return view('admin.products.index', compact('products'));
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
            'variants',
            'productAttributes',
        ]);

        return view('admin.products.show', compact('product'));
    }

    public function trashedProducts(): View
    {
        $products = Product::query()
            ->onlyTrashed()
            ->with(['category', 'primaryImage', 'images'])
            ->latest('deleted_at')
            ->get();

        return view('admin.products.trashed', compact('products'));
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
            'stock' => ['nullable', 'integer', 'min:0'],
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
            'stock' => ['nullable', 'integer', 'min:0'],
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

    public function storeCategory(Request $request, CategoryService $categoryService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['slug'] = trim((string) ($validated['slug'] ?? '')) !== '' ? $validated['slug'] : null;
        $validated['is_active'] = $request->boolean('is_active', true);

        $categoryService->create($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Category created successfully.');
    }

    public function updateCategory(Request $request, Category $category, CategoryService $categoryService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

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

    public function storeAttributeValue(Request $request, AttributeValueService $attributeValueService): RedirectResponse
    {
        $validated = $request->validate([
            'product_attribute_id' => ['required', 'integer', 'exists:product_attributes,id'],
            'value' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $attributeValueService->create($validated);

        return redirect()->route('admin')->with('success', 'Attribute value created successfully.');
    }

    public function updateAttributeValue(Request $request, AttributeValue $attributeValue, AttributeValueService $attributeValueService): RedirectResponse
    {
        $validated = $request->validate([
            'product_attribute_id' => ['required', 'integer', 'exists:product_attributes,id'],
            'value' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $attributeValueService->update($attributeValue, $validated);

        return redirect()->route('admin')->with('success', 'Attribute value updated successfully.');
    }

    public function deleteAttributeValue(AttributeValue $attributeValue, AttributeValueService $attributeValueService): RedirectResponse
    {
        $attributeValueService->delete($attributeValue);

        return redirect()->route('admin')->with('success', 'Attribute value deleted successfully.');
    }

    public function restoreAttributeValue(AttributeValue $attributeValue, AttributeValueService $attributeValueService): RedirectResponse
    {
        $attributeValueService->restore($attributeValue);

        return redirect()->route('admin')->with('success', 'Attribute value restored successfully.');
    }

    public function storeVariant(Request $request, ProductVariantService $productVariantService): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', 'unique:product_variants,sku'],
            'attribute_value_ids' => ['required', 'array'],
            'attribute_value_ids.*' => ['required', 'integer', 'exists:attribute_values,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $product = Product::query()->findOrFail($validated['product_id']);
        $productVariantService->createVariant($product, $validated);

        return redirect()->route('admin')->with('success', 'Variant created successfully.');
    }

    public function updateVariant(Request $request, Product $product, ProductVariant $variant, ProductVariantService $productVariantService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255'],
            'attribute_value_ids' => ['required', 'array'],
            'attribute_value_ids.*' => ['required', 'integer', 'exists:attribute_values,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $productVariantService->updateVariant($product, $variant, $validated);

        return redirect()->route('admin')->with('success', 'Variant updated successfully.');
    }

    public function deleteVariant(ProductVariant $variant, ProductVariantService $productVariantService): RedirectResponse
    {
        $productVariantService->deleteVariant($variant);

        return redirect()->route('admin')->with('success', 'Variant deleted successfully.');
    }

    public function restoreVariant(ProductVariant $variant, ProductVariantService $productVariantService): RedirectResponse
    {
        $productVariantService->restoreVariant($variant);

        return redirect()->route('admin')->with('success', 'Variant restored successfully.');
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
