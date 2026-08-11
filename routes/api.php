<?php

use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CustomerDashboardController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\DeliveryEligibilityController;
use App\Http\Controllers\Api\StorefrontController;
use App\Http\Controllers\Api\CustomerAddressController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\CmsController;
use App\Http\Controllers\Api\CustomerWishlistController;
use App\Http\Controllers\Api\CustomerVerificationController;
use App\Http\Controllers\Api\CustomerPasswordResetController;
use App\Http\Controllers\Api\CountryController;
use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');

Route::get('/storefront/home', [StorefrontController::class, 'home']);
Route::get('/storefront/lookbooks', [StorefrontController::class, 'lookbooks']);
Route::get('/countries', [CountryController::class, 'index']);
Route::get('/storefront/header', [StorefrontController::class, 'header']);
Route::get('/storefront/categories', [StorefrontController::class, 'categories']);
Route::get('/storefront/featured-categories', [StorefrontController::class, 'featuredCategories']);
Route::get('/storefront/master-filters', [StorefrontController::class, 'masterFilters']);
Route::get('/storefront/departments', [StorefrontController::class, 'departments']);
Route::get('/storefront/products', [StorefrontController::class, 'products']);
Route::get('/storefront/products/id/{id}', [StorefrontController::class, 'productById']);
Route::get('/storefront/search', [StorefrontController::class, 'search']);
Route::get('/storefront/products/{product:slug}', [StorefrontController::class, 'product']);
Route::get('/storefront/top-offers', [StorefrontController::class, 'topOffers']);
Route::get('/storefront/faqs', [StorefrontController::class, 'faqs']);
Route::get('/cms/{slug}', [CmsController::class, 'show']);

Route::post('/delivery/check', [DeliveryEligibilityController::class, 'check']);
Route::get('/shipping/rates', [CheckoutController::class, 'getShippingRates']);
Route::post('/coupons/validate', [CouponController::class, 'validate'])->middleware('web');
Route::post('/checkout', [CheckoutController::class, 'create'])->middleware('web');
Route::post('/checkout/retry', [CheckoutController::class, 'retry'])->middleware('web');
Route::post('/checkout/payment-status', [CheckoutController::class, 'paymentStatus'])->middleware('web');
Route::post('/webhooks/stripe', [\App\Http\Controllers\Api\StripeWebhookController::class, 'handle']);

// Customer Auth API endpoints (Public)
Route::post('/customer/login', [CustomerAddressController::class, 'login']);
Route::post('/customer/register', [CustomerAddressController::class, 'register']);
Route::post('/forgot-password', [CustomerPasswordResetController::class, 'sendLink']);
Route::post('/reset-password', [CustomerPasswordResetController::class, 'reset']);
Route::get('/email/verify/{id}/{hash}', [CustomerVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('customer.verification.verify');

Route::middleware(['web'])->group(function () {
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::post('/cart/replace', [CartController::class, 'replace']);
    Route::put('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);
    Route::post('/cart/clear', [CartController::class, 'clear']);
    
    Route::get('/customer/debug-session', function (\Illuminate\Http\Request $request) {
        return response()->json([
            'session_id' => session()->getId(),
            'customer_id' => session()->get('customer_id'),
            'auth_check' => \Illuminate\Support\Facades\Auth::guard('customer')->check(),
            'user' => \Illuminate\Support\Facades\Auth::guard('customer')->user(),
            'cookies' => $request->cookies->all(),
        ]);
    });
});

// Protected Customer Routes (Sanctum Authenticated)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/customer/logout', [CustomerAddressController::class, 'logout']);
    Route::get('/me', [CustomerDashboardController::class, 'me']);
    Route::get('/customer/dashboard-summary', [CustomerDashboardController::class, 'dashboardSummary']);
    
    Route::get('/customer/addresses', [CustomerAddressController::class, 'index']);
    Route::post('/customer/addresses', [CustomerAddressController::class, 'store']);
    Route::put('/customer/addresses/{id}', [CustomerAddressController::class, 'update']);
    Route::delete('/customer/addresses/{id}', [CustomerAddressController::class, 'destroy']);
    Route::post('/customer/addresses/{id}/default-shipping', [CustomerAddressController::class, 'setDefaultShipping']);
    Route::post('/customer/addresses/{id}/default-billing', [CustomerAddressController::class, 'setDefaultBilling']);
    Route::post('/customer/change-password', [CustomerDashboardController::class, 'changePassword']);
    Route::get('/customer/orders', [CustomerDashboardController::class, 'orders']);
    Route::get('/customer/orders/{id}', [CustomerDashboardController::class, 'showOrder']);

    // Wishlist Routes
    Route::get('/customer/wishlist', [CustomerWishlistController::class, 'index']);
    Route::post('/customer/wishlist', [CustomerWishlistController::class, 'store']);
    Route::delete('/customer/wishlist/{productId}', [CustomerWishlistController::class, 'destroy']);
});
