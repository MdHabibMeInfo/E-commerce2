<?php

use App\Http\Controllers\Web\AdminController;
use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\ShopController;
use Illuminate\Support\Facades\Route;

Route::controller(HomeController::class)->group(function () {
    Route::get('/', 'index')->name('home');
});

Route::controller(ShopController::class)->group(function () {
    Route::get('/shop/{category?}', 'index')
        ->where('category', '[a-z0-9-]+')
        ->name('shop');
});

Route::controller(CheckoutController::class)->group(function () {
    Route::get('/checkout', 'index')->name('checkout');
    Route::post('/checkout', 'store')->name('checkout.store');
    Route::get('/checkout/success/{order}', 'success')->name('checkout.success');
});

Route::get('/admin', [AdminController::class, 'index'])->name('admin');
Route::get('/admin/orders', [AdminController::class, 'orders'])->name('admin.orders');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/categories', [AdminController::class, 'categoriesIndex'])->name('categories.index');
    Route::get('/categories/create', [AdminController::class, 'createCategory'])->name('categories.create');
    Route::post('/categories', [AdminController::class, 'storeCategory'])->name('categories.store');
    Route::get('/categories/trashed', [AdminController::class, 'trashedCategories'])->name('categories.trashed');
    Route::get('/categories/{category}', [AdminController::class, 'showCategory'])->name('categories.show');
    Route::get('/categories/{category}/edit', [AdminController::class, 'editCategory'])->name('categories.edit');
    Route::put('/categories/{category}', [AdminController::class, 'updateCategory'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminController::class, 'destroyCategory'])->name('categories.destroy');
    Route::post('/categories/{category}/restore', [AdminController::class, 'restoreCategory'])->name('categories.restore');

    Route::get('/attributes', [AdminController::class, 'attributesIndex'])->name('attributes.index');
    Route::get('/attributes/create', [AdminController::class, 'createAttribute'])->name('attributes.create');
    Route::post('/attributes', [AdminController::class, 'storeAttribute'])->name('attributes.store');
    Route::get('/attributes/trashed', [AdminController::class, 'trashedAttributes'])->name('attributes.trashed');
    Route::get('/attributes/{attribute}', [AdminController::class, 'showAttribute'])->name('attributes.show');
    Route::get('/attributes/{attribute}/edit', [AdminController::class, 'editAttribute'])->name('attributes.edit');
    Route::put('/attributes/{attribute}', [AdminController::class, 'updateAttribute'])->name('attributes.update');
    Route::delete('/attributes/{attribute}', [AdminController::class, 'destroyAttribute'])->name('attributes.destroy');
    Route::post('/attributes/{attribute}/restore', [AdminController::class, 'restoreAttribute'])->name('attributes.restore');

    Route::get('/products', [AdminController::class, 'productsIndex'])->name('products.index');
    Route::get('/products/create', [AdminController::class, 'createProduct'])->name('products.create');
    Route::post('/products', [AdminController::class, 'storeProduct'])->name('products.store');
    Route::get('/products/trashed', [AdminController::class, 'trashedProducts'])->name('products.trashed');
    Route::get('/products/{product}', [AdminController::class, 'showProduct'])->name('products.show');
    Route::get('/products/{product}/edit', [AdminController::class, 'editProduct'])->name('products.edit');
    Route::put('/products/{product}', [AdminController::class, 'updateProduct'])->name('products.update');
    Route::delete('/products/{product}', [AdminController::class, 'destroyProduct'])->name('products.destroy');
    Route::post('/products/{product}/restore', [AdminController::class, 'restoreProduct'])->name('products.restore');

    Route::post('/products/{product}/images', [AdminController::class, 'uploadProductImages'])->name('products.images.store');
    Route::patch('/products/{product}/images/{image}/primary', [AdminController::class, 'setPrimaryProductImage'])->name('products.images.primary');
    Route::delete('/products/{product}/images/{image}', [AdminController::class, 'destroyProductImage'])->name('products.images.destroy');
});

Route::prefix('products')->name('products.')->controller(ProductController::class)->group(function () {
    Route::get('/{product}', 'show')
        ->where('product', '[a-z0-9-]+')
        ->name('show');
});
