<?php

use App\Http\Controllers\Web\AccountSecurityController;
use App\Http\Controllers\Web\AdminController;
use App\Http\Controllers\Web\AdminCouponController;
use App\Http\Controllers\Web\AdminCustomerController;
use App\Http\Controllers\Web\AdminReviewController;
use App\Http\Controllers\Web\AdminRoleController;
use App\Http\Controllers\Web\AdminSalesReportController;
use App\Http\Controllers\Web\AdminSessionController;
use App\Http\Controllers\Web\AdminSettingsController;
use App\Http\Controllers\Web\AdminUserController;
use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\CustomerRegistrationController;
use App\Http\Controllers\Web\EmailVerificationController;
use App\Http\Controllers\Web\HeroBannerController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\OrderController;
use App\Http\Controllers\Web\PasswordResetController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\ProductReviewController;
use App\Http\Controllers\Web\ShopController;
use App\Http\Controllers\Web\TwoFactorController;
use Illuminate\Support\Facades\Route;

Route::controller(HomeController::class)->group(function () {
    Route::get('/', 'index')->name('home');
    Route::get('/about-us', 'about')->name('about');
    Route::get('/contact', 'contact')->name('contact');
    Route::get('/terms-and-conditions', 'terms')->name('terms');
    Route::get('/privacy-policy', 'privacyPolicy')->name('privacy');
    Route::get('/return-refund-policy', 'returnRefundPolicy')->name('returns');
});

Route::get('/search/suggestions', [ShopController::class, 'suggestions'])
    ->middleware('throttle:120,1')
    ->name('shop.suggestions');

Route::controller(ShopController::class)->group(function () {
    Route::get('/shop/{category?}', 'index')
        ->where('category', '[a-z0-9-]+')
        ->name('shop');
});

Route::controller(CheckoutController::class)->group(function () {
    Route::get('/checkout', 'index')->name('checkout');
    Route::post('/checkout/coupon', 'applyCoupon')->middleware('throttle:10,1')->name('checkout.coupon.apply');
    Route::post('/checkout/coupon/remove', 'removeCoupon')->middleware('throttle:10,1')->name('checkout.coupon.remove');
    Route::post('/checkout', 'store')->name('checkout.store');
    Route::get('/checkout/success/{order}', 'success')->name('checkout.success');
});

Route::get('/login', [AdminSessionController::class, 'create'])->name('login');
Route::post('/login', [AdminSessionController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
Route::middleware('guest')->group(function () {
    Route::get('/register', [CustomerRegistrationController::class, 'create'])->name('register');
    Route::post('/register', [CustomerRegistrationController::class, 'store'])->middleware('throttle:5,1')->name('register.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'send'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
});
Route::post('/logout', [AdminSessionController::class, 'destroy'])->middleware('auth')->name('logout');
Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
    ->middleware('auth')
    ->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['auth', 'signed', 'throttle:6,1'])
    ->name('verification.verify');
Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');
Route::get('/email/change/{user}/{hash}', [EmailVerificationController::class, 'verifyPendingEmail'])
    ->middleware(['auth', 'signed', 'management.2fa', 'throttle:6,1'])
    ->name('verification.email-change');

Route::get('/two-factor-challenge', [TwoFactorController::class, 'challenge'])
    ->middleware(['auth', 'management.verified'])
    ->name('two-factor.challenge');
Route::post('/two-factor-challenge', [TwoFactorController::class, 'verify'])
    ->middleware(['auth', 'management.verified', 'throttle:two-factor'])
    ->name('two-factor.verify');
Route::middleware(['auth', 'can:access-admin', 'management.verified'])
    ->prefix('admin/two-factor')->name('admin.two-factor.')->group(function () {
        Route::get('/setup', [TwoFactorController::class, 'setup'])->name('setup');
        Route::post('/setup', [TwoFactorController::class, 'confirm'])->middleware('throttle:two-factor')->name('confirm');
    });
Route::middleware(['auth', 'management.2fa'])
    ->prefix('account/security/two-factor')->name('account.security.two-factor.')->group(function () {
        Route::get('/setup', [TwoFactorController::class, 'setup'])->name('setup');
        Route::post('/setup', [TwoFactorController::class, 'confirm'])->middleware('throttle:two-factor')->name('confirm');
        Route::delete('/', [TwoFactorController::class, 'disableCustomer'])->middleware(['throttle:two-factor'])->name('disable');
    });

Route::middleware(['auth', 'management.verified', 'management.2fa'])->group(function () {
    Route::get('/account/profile', [AccountSecurityController::class, 'profile'])->name('account.profile');
    Route::put('/account/profile', [AccountSecurityController::class, 'updateProfile'])
        ->middleware(['management.2fa', 'throttle:5,1'])->name('account.profile.update');
    Route::delete('/account/profile/photo', [AccountSecurityController::class, 'deleteProfilePhoto'])
        ->middleware(['management.2fa', 'throttle:5,1'])->name('account.profile.photo.destroy');
    Route::get('/account/orders', [OrderController::class, 'index'])->name('account.orders');
    Route::get('/account/orders/history', [OrderController::class, 'history'])->name('account.orders.history');
    Route::post('/account/orders/{order}/cancel', [OrderController::class, 'cancel'])
        ->middleware('throttle:5,1')->name('account.orders.cancel');
    Route::get('/account/orders/{order}', [OrderController::class, 'show'])->name('account.orders.show');
    Route::get('/account/security', [AccountSecurityController::class, 'index'])
        ->middleware('management.2fa')->name('account.security');
    Route::put('/account/security/email', [AccountSecurityController::class, 'updateEmail'])
        ->middleware(['management.2fa', 'throttle:5,1'])->name('account.security.email.update');
    Route::post('/account/security/email/resend', [AccountSecurityController::class, 'resendEmailVerification'])
        ->middleware(['management.2fa', 'throttle:6,1'])->name('account.security.email.resend');
    Route::put('/account/security/password', [AccountSecurityController::class, 'updatePassword'])
        ->middleware(['management.2fa', 'throttle:5,1'])->name('account.security.password.update');
    Route::post('/account/security/recovery-codes', [AccountSecurityController::class, 'regenerateRecoveryCodes'])
        ->middleware(['management.2fa', 'throttle:6,1'])->name('account.security.recovery-codes');
    Route::delete('/account/security/sessions/{sessionId}', [AccountSecurityController::class, 'destroySession'])
        ->middleware(['management.2fa', 'throttle:5,1'])->where('sessionId', '[A-Za-z0-9,-]+')->name('account.security.sessions.destroy');
    Route::delete('/account/security/sessions', [AccountSecurityController::class, 'destroyOtherSessions'])
        ->middleware(['management.2fa', 'throttle:5,1'])->name('account.security.sessions.destroy-others');

    Route::get('/admin', [AdminController::class, 'index'])
        ->middleware(['can:access-admin', 'management.2fa'])
        ->name('admin');
    Route::get('/admin/orders', [AdminController::class, 'orders'])
        ->middleware(['can:access-admin', 'can:orders.view', 'management.2fa'])
        ->name('admin.orders');
    Route::get('/admin/orders/{order}', [OrderController::class, 'adminShow'])
        ->middleware(['can:access-admin', 'can:orders.view', 'management.2fa'])
        ->name('admin.orders.show');
    Route::get('/admin/orders/{order}/payment-proof', [OrderController::class, 'paymentProof'])
        ->middleware(['can:access-admin', 'can:orders.view', 'management.2fa'])
        ->name('admin.orders.payment-proof');
    Route::patch('/admin/orders/{order}/payment', [OrderController::class, 'reviewPayment'])
        ->middleware(['can:access-admin', 'can:orders.view', 'management.2fa', 'throttle:10,1'])
        ->name('admin.orders.payment.review');
    Route::post('/admin/orders/{order}/shipments', [OrderController::class, 'createShipment'])
        ->middleware(['can:access-admin', 'management.2fa', 'throttle:5,1'])
        ->name('admin.orders.shipments.store');
    Route::post('/admin/orders/{order}/courier-history', [OrderController::class, 'checkCustomerCourierHistory'])
        ->middleware(['can:access-admin', 'can:orders.view', 'management.2fa', 'throttle:10,1'])
        ->name('admin.orders.courier-history');
    Route::post('/admin/orders/{order}/shipments/reconcile', [OrderController::class, 'reconcileShipment'])
        ->middleware(['can:access-admin', 'management.2fa', 'throttle:5,1'])
        ->name('admin.orders.shipments.reconcile');
    Route::patch('/admin/orders/{order}/cancel', [OrderController::class, 'adminCancel'])
        ->middleware(['can:access-admin', 'can:orders.view', 'management.2fa', 'throttle:10,1'])
        ->name('admin.orders.cancel');

    Route::prefix('admin')->name('admin.')->middleware(['can:access-admin', 'management.2fa'])->group(function () {
        Route::get('/settings', [AdminSettingsController::class, 'edit'])->name('settings');
        Route::get('/settings/hero-targets', [AdminSettingsController::class, 'heroTargetSuggestions'])
            ->middleware('throttle:60,1')->name('settings.hero-targets');
        Route::patch('/settings/branding', [AdminSettingsController::class, 'updateBranding'])
            ->middleware('can:settings.update')->name('settings.branding.update');
        Route::post('/settings/hero-banners', [HeroBannerController::class, 'store'])
            ->middleware('can:settings.update')->name('settings.hero-banners.store');
        Route::put('/settings/hero-banners/{heroBanner}', [HeroBannerController::class, 'update'])
            ->middleware('can:settings.update')->name('settings.hero-banners.update');
        Route::delete('/settings/hero-banners/{heroBanner}', [HeroBannerController::class, 'destroy'])
            ->middleware('can:settings.update')->name('settings.hero-banners.destroy');
        Route::patch('/settings/security', [AdminSettingsController::class, 'updateSensitive'])
            ->middleware(['can:system-owner-settings', 'throttle:5,1'])->name('settings.security.update');

        Route::get('/profile', [AdminController::class, 'profile'])
            ->middleware('can:access-admin')
            ->name('profile');
        Route::put('/profile', [AdminController::class, 'updateProfile'])
            ->middleware('can:access-admin')
            ->name('profile.update');
        Route::delete('/profile/photo', [AdminController::class, 'deleteProfilePhoto'])
            ->middleware('can:access-admin')
            ->name('profile.photo.destroy');
        Route::get('/notifications', [AdminController::class, 'notifications'])
            ->name('notifications');
        Route::get('/notifications/unread-count', [AdminController::class, 'unreadNotifications'])
            ->name('notifications.unread');

        Route::get('/reviews', [AdminReviewController::class, 'index'])->middleware('can:reviews.view')->name('reviews.index');
        Route::get('/reviews/{review}', [AdminReviewController::class, 'show'])->middleware('can:reviews.view')->name('reviews.show');
        Route::patch('/reviews/{review}', [AdminReviewController::class, 'update'])
            ->middleware(['can:reviews.moderate', 'throttle:10,1'])->name('reviews.update');
        Route::get('/reports/sales', [AdminSalesReportController::class, 'index'])
            ->middleware('can:reports.view')->name('reports.sales');
        Route::get('/reports/sales/products', [AdminSalesReportController::class, 'productSuggestions'])
            ->middleware(['can:reports.view', 'throttle:60,1'])->name('reports.sales.products');

        Route::get('/roles', [AdminRoleController::class, 'index'])->middleware(['can:roles.view', 'can:system-owner-settings'])->name('roles.index');
        Route::post('/roles', [AdminRoleController::class, 'store'])->middleware(['can:roles.create', 'can:system-owner-settings'])->name('roles.store');
        Route::put('/roles/{role}', [AdminRoleController::class, 'update'])->middleware(['can:roles.update', 'can:system-owner-settings'])->name('roles.update');
        Route::put('/roles/{role}/permissions', [AdminRoleController::class, 'updatePermissions'])->middleware(['can:roles.update', 'can:system-owner-settings'])->name('roles.permissions.update');
        Route::patch('/roles/{role}/status', [AdminRoleController::class, 'updateStatus'])->middleware(['can:roles.update', 'can:system-owner-settings'])->name('roles.status.update');
        Route::delete('/roles/{role}', [AdminRoleController::class, 'destroy'])->middleware(['can:roles.delete', 'can:system-owner-settings'])->name('roles.destroy');

        Route::get('/users', [AdminUserController::class, 'index'])->middleware(['can:users.view', 'can:system-owner-settings'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->middleware(['can:users.create', 'can:system-owner-settings'])->name('users.store');
        Route::put('/users/{user}/roles', [AdminUserController::class, 'updateRoles'])->middleware(['can:users.update', 'can:system-owner-settings'])->name('users.roles.update');
        Route::patch('/users/{user}/status', [AdminUserController::class, 'updateStatus'])->middleware(['can:users.suspend', 'can:system-owner-settings'])->name('users.status.update');
        Route::get('/customers', [AdminCustomerController::class, 'index'])->middleware('can:customers.view')->name('customers.index');
        Route::get('/customers/{user}', [AdminCustomerController::class, 'show'])->middleware('can:customers.view')->name('customers.show');
        Route::patch('/customers/{user}/status', [AdminCustomerController::class, 'updateStatus'])->middleware('can:customers.update')->name('customers.status.update');
        Route::get('/coupons', [AdminCouponController::class, 'index'])->middleware('can:coupons.view')->name('coupons.index');
        Route::get('/coupons/targets', [AdminCouponController::class, 'targetSuggestions'])->middleware(['can:coupons.view', 'throttle:60,1'])->name('coupons.targets');
        Route::post('/coupons', [AdminCouponController::class, 'store'])->middleware('can:coupons.create')->name('coupons.store');
        Route::put('/coupons/{coupon}', [AdminCouponController::class, 'update'])->middleware('can:coupons.update')->name('coupons.update');
        Route::patch('/coupons/{coupon}/status', [AdminCouponController::class, 'updateStatus'])->middleware('can:coupons.update')->name('coupons.status.update');
        Route::get('/security/events', [AdminController::class, 'securityEvents'])->middleware('can:audit-logs.view')->name('security.events');

        Route::get('/categories', [AdminController::class, 'categoriesIndex'])->middleware('can:categories.view')->name('categories.index');
        Route::get('/categories/suggestions', [AdminController::class, 'categorySuggestions'])->middleware('can:categories.view')->name('categories.suggestions');
        Route::get('/categories/create', [AdminController::class, 'createCategory'])->middleware('can:categories.create')->name('categories.create');
        Route::post('/categories', [AdminController::class, 'storeCategory'])->middleware('can:categories.create')->name('categories.store');
        Route::get('/categories/trashed', [AdminController::class, 'trashedCategories'])->middleware('can:categories.view')->name('categories.trashed');
        Route::get('/categories/{category}', [AdminController::class, 'showCategory'])->middleware('can:categories.view')->name('categories.show');
        Route::get('/categories/{category}/edit', [AdminController::class, 'editCategory'])->middleware('can:categories.update')->name('categories.edit');
        Route::put('/categories/{category}', [AdminController::class, 'updateCategory'])->middleware('can:categories.update')->name('categories.update');
        Route::delete('/categories/{category}', [AdminController::class, 'destroyCategory'])->middleware('can:categories.delete')->name('categories.destroy');
        Route::post('/categories/{category}/restore', [AdminController::class, 'restoreCategory'])->middleware('can:categories.update')->name('categories.restore');

        Route::get('/attributes', [AdminController::class, 'attributesIndex'])->middleware('can:attributes.view')->name('attributes.index');
        Route::get('/attributes/suggestions', [AdminController::class, 'attributeSuggestions'])->middleware('can:attributes.view')->name('attributes.suggestions');
        Route::get('/attributes/create', [AdminController::class, 'createAttribute'])->middleware('can:attributes.create')->name('attributes.create');
        Route::post('/attributes', [AdminController::class, 'storeAttribute'])->middleware('can:attributes.create')->name('attributes.store');
        Route::get('/attributes/trashed', [AdminController::class, 'trashedAttributes'])->middleware('can:attributes.view')->name('attributes.trashed');
        Route::get('/attributes/{attribute}', [AdminController::class, 'showAttribute'])->middleware('can:attributes.view')->name('attributes.show');
        Route::get('/attributes/{attribute}/edit', [AdminController::class, 'editAttribute'])->middleware('can:attributes.update')->name('attributes.edit');
        Route::put('/attributes/{attribute}', [AdminController::class, 'updateAttribute'])->middleware('can:attributes.update')->name('attributes.update');
        Route::delete('/attributes/{attribute}', [AdminController::class, 'destroyAttribute'])->middleware('can:attributes.delete')->name('attributes.destroy');
        Route::post('/attributes/{attribute}/restore', [AdminController::class, 'restoreAttribute'])->middleware('can:attributes.update')->name('attributes.restore');
        Route::post('/attributes/{attribute}/values', [AdminController::class, 'storeAttributeValue'])->middleware('can:attribute-values.create')->name('attributes.values.store');
        Route::put('/attributes/{attribute}/values/{attributeValue}', [AdminController::class, 'updateAttributeValue'])->middleware('can:attribute-values.update')->name('attributes.values.update');
        Route::delete('/attributes/{attribute}/values/{attributeValue}', [AdminController::class, 'deleteAttributeValue'])->middleware('can:attribute-values.delete')->name('attributes.values.destroy');
        Route::post('/attributes/{attribute}/values/{attributeValue}/restore', [AdminController::class, 'restoreAttributeValue'])->middleware('can:attribute-values.update')->withTrashed()->name('attributes.values.restore');

        Route::get('/products', [AdminController::class, 'productsIndex'])->middleware('can:products.view')->name('products.index');
        Route::get('/products/suggestions', [AdminController::class, 'productSuggestions'])->middleware('can:products.view')->name('products.suggestions');
        Route::get('/products/create', [AdminController::class, 'createProduct'])->middleware('can:products.create')->name('products.create');
        Route::post('/products', [AdminController::class, 'storeProduct'])->middleware('can:products.create')->name('products.store');
        Route::get('/products/trashed', [AdminController::class, 'trashedProducts'])->middleware('can:products.view')->name('products.trashed');
        Route::put('/products/{product}/attributes', [AdminController::class, 'updateProductAttributes'])->middleware('can:products.update')->name('products.attributes.update');
        Route::post('/products/{product}/variants', [AdminController::class, 'storeVariant'])->middleware(['can:products.view', 'can:variants.create'])->name('products.variants.store');
        Route::put('/products/{product}/variants/{variant}', [AdminController::class, 'updateVariant'])->middleware(['can:products.view', 'can:variants.update'])->scopeBindings()->name('products.variants.update');
        Route::patch('/products/{product}/variants/{variant}/availability', [AdminController::class, 'updateVariantAvailability'])->middleware(['can:products.view', 'can:variants.update'])->scopeBindings()->name('products.variants.availability');
        Route::delete('/products/{product}/variants/{variant}', [AdminController::class, 'deleteVariant'])->middleware(['can:products.view', 'can:variants.delete'])->scopeBindings()->name('products.variants.destroy');
        Route::post('/products/{product}/variants/{variant}/restore', [AdminController::class, 'restoreVariant'])->middleware(['can:products.view', 'can:variants.update'])->scopeBindings()->withTrashed()->name('products.variants.restore');
        Route::get('/products/{product}', [AdminController::class, 'showProduct'])->middleware('can:products.view')->name('products.show');
        Route::get('/products/{product}/edit', [AdminController::class, 'editProduct'])->middleware('can:products.update')->name('products.edit');
        Route::put('/products/{product}', [AdminController::class, 'updateProduct'])->middleware('can:products.update')->name('products.update');
        Route::patch('/products/{product}/reviews/{review}/approval', [AdminReviewController::class, 'updateProductApproval'])->middleware('can:reviews.moderate')->name('products.reviews.approval');
        Route::patch('/products/{product}/availability', [AdminController::class, 'updateProductAvailability'])->middleware('can:products.update')->name('products.availability');
        Route::delete('/products/{product}', [AdminController::class, 'destroyProduct'])->middleware('can:products.delete')->name('products.destroy');
        Route::post('/products/{product}/restore', [AdminController::class, 'restoreProduct'])->middleware('can:products.update')->name('products.restore');

        Route::post('/products/{product}/images', [AdminController::class, 'uploadProductImages'])->middleware(['can:products.update', 'can:product-images.create'])->name('products.images.store');
        Route::patch('/products/{product}/images/{image}/primary', [AdminController::class, 'setPrimaryProductImage'])->middleware(['can:products.update', 'can:product-images.update'])->name('products.images.primary');
        Route::delete('/products/{product}/images/{image}', [AdminController::class, 'destroyProductImage'])->middleware(['can:products.update', 'can:product-images.delete'])->name('products.images.destroy');
    });
});

Route::prefix('products')->name('products.')->controller(ProductController::class)->group(function () {
    Route::post('/{product}/reviews', ProductReviewController::class)
        ->where('product', '[a-z0-9-]+')
        ->middleware(['auth', 'throttle:3,1'])
        ->name('reviews.store');

    Route::get('/{product}', 'show')
        ->where('product', '[a-z0-9-]+')
        ->name('show');
});
