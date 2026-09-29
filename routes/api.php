<?php

use App\Http\Controllers\Api\AttributeValueController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductAttributeAssignmentController;
use App\Http\Controllers\Api\ProductAttributeController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductImageController;
use App\Http\Controllers\Api\ProductVariantController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/items', [CartController::class, 'store']);
    Route::patch('/cart/items/{cartItem}', [CartController::class, 'update']);
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'management.verified', 'management.2fa'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    /*
    |--------------------------------------------------------------------------
    | Category Routes
    |--------------------------------------------------------------------------
    */

    Route::apiResource('categories', CategoryController::class);

    Route::patch(
        '/categories/{category}/restore',
        [CategoryController::class, 'restore']
    )->withTrashed();

    /*
    |--------------------------------------------------------------------------
    | Product Routes
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/products',
        [ProductController::class, 'index']
    );

    Route::get(
        '/products/{product}',
        [ProductController::class, 'show']
    );

    Route::post(
        '/products',
        [ProductController::class, 'store']
    );

    Route::put(
        '/products/{product}',
        [ProductController::class, 'update']
    );

    Route::delete(
        '/products/{product}',
        [ProductController::class, 'destroy']
    );

    Route::patch(
        '/products/{product}/restore',
        [ProductController::class, 'restore']
    )->withTrashed();

    /*
    |--------------------------------------------------------------------------
    | Product Image Routes
    |--------------------------------------------------------------------------
    */

    Route::scopeBindings()->group(function () {

        Route::get(
            '/products/{product}/images',
            [ProductImageController::class, 'index']
        );

        Route::get(
            '/products/{product}/images/{productImage}',
            [ProductImageController::class, 'show']
        );

        Route::post(
            '/products/{product}/images',
            [ProductImageController::class, 'store']
        );

        Route::put(
            '/products/{product}/images/{productImage}',
            [ProductImageController::class, 'update']
        );

        Route::delete(
            '/products/{product}/images/{productImage}',
            [ProductImageController::class, 'destroy']
        );

        Route::patch(
            '/products/{product}/images/{productImage}/primary',
            [ProductImageController::class, 'setPrimary']
        );

        Route::post(
            '/products/{product}/images/reorder',
            [ProductImageController::class, 'reorder']
        );
    });

    /*
    |--------------------------------------------------------------------------
    | Product Attribute Assignment Routes
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/products/{product}/attributes',
        [ProductAttributeAssignmentController::class, 'index']
    );

    Route::put(
        '/products/{product}/attributes',
        [ProductAttributeAssignmentController::class, 'update']
    );

    /*
    |--------------------------------------------------------------------------
    | Product Attribute Routes
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'product-attributes',
        ProductAttributeController::class
    );

    Route::patch(
        '/product-attributes/{productAttribute}/restore',
        [ProductAttributeController::class, 'restore']
    )->withTrashed();

    /*
    |--------------------------------------------------------------------------
    | Attribute Value Routes
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'attribute-values',
        AttributeValueController::class
    )->parameters([
        'attribute-values' => 'attributeValue',
    ]);

    Route::patch(
        '/attribute-values/{attributeValue}/restore',
        [AttributeValueController::class, 'restore']
    )->withTrashed();

    /*
    |--------------------------------------------------------------------------
    | Product Variant Routes
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/products/{product}/variants',
        [ProductVariantController::class, 'store']
    );

    Route::scopeBindings()->group(function () {

        Route::get(
            '/products/{product}/variants',
            [ProductVariantController::class, 'index']
        );

        Route::get(
            '/products/{product}/variants/{variant}',
            [ProductVariantController::class, 'show']
        );

        Route::put(
            '/products/{product}/variants/{variant}',
            [ProductVariantController::class, 'update']
        );

        Route::delete(
            '/products/{product}/variants/{variant}',
            [ProductVariantController::class, 'destroy']
        );
    });
});

// $user = \App\Models\User::find(1);

// $user->tokens()->delete();

// $token = $user->createToken('development-test-token')->plainTextToken;

// "2|aCWWPbLnEdDBvWJIcnXiiygbMXBz2vUFziap5Wzm94f61e7b"
