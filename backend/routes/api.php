<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Seller\ShopController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\Buyer\ProductController;
use App\Http\Controllers\Api\Buyer\CartController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Buyer\PaymentController as BuyerPaymentController;
use App\Http\Controllers\Api\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Api\Admin\ShopController as AdminShopController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\ShippingController;

// API Version 1
Route::prefix('v1')->group(function () {

  // Public integration probe for local frontend/API checks.
  Route::get('/health', function () {
    DB::connection()->getPdo();

    return response()->json([
      'success' => true,
      'data' => [
        'service' => 'kabita-api',
        'status' => 'ok',
        'database' => 'connected',
      ],
    ]);
  })->name('health');

  // Public Auth Routes
  Route::prefix('auth')->middleware('throttle:auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/resend-code', [AuthController::class, 'resendCode']);
  });

  // Protected Routes
  Route::middleware(['auth:sanctum', 'sanitize'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
  });

  // Shop Routes
  Route::middleware(['auth:sanctum', 'seller', 'throttle:api', 'sanitize'])->group(function () {
    Route::post('/shops', [ShopController::class, 'create'])->name('shops.create');
    Route::get('/shops/my-shop', [ShopController::class, 'myShop'])->name('shops.my-shop');
    Route::put('/shops/{shop}', [ShopController::class, 'update'])->name('shops.update');
  });

  Route::middleware(['auth:sanctum', 'seller', 'throttle:api', 'sanitize'])->prefix('seller/products')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\Seller\ProductController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\Seller\ProductController::class, 'store']);
    Route::get('/{slug}', [\App\Http\Controllers\Api\Seller\ProductController::class, 'show']);
    Route::put('/{slug}', [\App\Http\Controllers\Api\Seller\ProductController::class, 'update']);
    Route::post('/{slug}', [\App\Http\Controllers\Api\Seller\ProductController::class, 'update']);
    Route::delete('/{slug}', [\App\Http\Controllers\Api\Seller\ProductController::class, 'destroy']);
  });

  // Category Routes
  Route::middleware('throttle:api')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');
  });

  // Public Product Routes
  Route::prefix('public')->middleware('throttle:api')->group(function () {
    Route::get('/products', [ProductController::class, 'index'])->name('public.products.index');
    Route::get('/products/{slug}', [ProductController::class, 'show'])->name('public.products.show');
  });

  // Shipping Routes (Buyer only)
  Route::middleware(['auth:sanctum', 'buyer', 'throttle:api', 'sanitize'])->prefix('shipping')->group(function () {
    Route::get('/options', [ShippingController::class, 'options'])->name('shipping.options');
    Route::post('/calculate', [ShippingController::class, 'calculate'])->name('shipping.calculate');
  });

  // Location Routes (Buyer only)
  Route::middleware(['auth:sanctum', 'buyer', 'throttle:api', 'sanitize'])->prefix('locations')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\Buyer\CodLocationController::class, 'index'])->name('locations.index');
    Route::post('/', [\App\Http\Controllers\Api\Buyer\CodLocationController::class, 'store'])->name('locations.store');
    Route::put('/{codLocation}', [\App\Http\Controllers\Api\Buyer\CodLocationController::class, 'update'])->name('locations.update');
    Route::delete('/{codLocation}', [\App\Http\Controllers\Api\Buyer\CodLocationController::class, 'destroy'])->name('locations.destroy');
  });

  // Cart Routes (Buyer only)
  Route::middleware(['auth:sanctum', 'buyer', 'throttle:api', 'sanitize'])->prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('cart.index');
    Route::post('/items', [CartController::class, 'store'])->name('cart.store');
    Route::put('/items/{cartItem}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::delete('/clear', [CartController::class, 'clear'])->name('cart.clear');
    Route::get('/validate', [CartController::class, 'validateForCheckout'])->name('cart.validate');
  });

  // Checkout Route (Buyer only)
  Route::middleware(['auth:sanctum', 'buyer', 'throttle:api', 'sanitize'])->post('/checkout', \App\Http\Controllers\Api\Buyer\CheckoutController::class)->name('checkout.process');

  // Payment Routes (Buyer only)
  Route::middleware(['auth:sanctum', 'buyer', 'throttle:api', 'sanitize'])->post('/payments/{payment}/upload', [BuyerPaymentController::class, 'upload'])->name('payments.upload');

  // Payment Settings Routes (Buyer only)
  Route::middleware(['auth:sanctum', 'buyer', 'throttle:api', 'sanitize'])->get('/buyer/payment-settings', [\App\Http\Controllers\Api\Buyer\PaymentSettingController::class, 'show'])->name('payment-settings.show');

  // Admin Routes
  Route::middleware(['auth:sanctum', 'admin', 'throttle:api', 'sanitize'])->group(function () {
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category:slug}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category:slug}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Admin Product Verification
    Route::get('/products/pending', [AdminProductController::class, 'pending'])->name('admin.products.pending');
    Route::patch('/products/{product}/approve', [AdminProductController::class, 'approve'])->name('admin.products.approve');
    Route::patch('/products/{product}/reject', [AdminProductController::class, 'reject'])->name('admin.products.reject');

    // Admin Payment Verification
    Route::get('/payments/pending', [AdminPaymentController::class, 'index'])->name('admin.payments.pending');
    Route::get('/payments/{payment}', [AdminPaymentController::class, 'show'])->name('admin.payments.show');
    Route::patch('/payments/{payment}/verify', [AdminPaymentController::class, 'verify'])->name('admin.payments.verify');
    Route::patch('/payments/{payment}/reject', [AdminPaymentController::class, 'reject'])->name('admin.payments.reject');

    // Admin Analytics
    Route::prefix('analytics')->group(function () {
      Route::get('/platform', [\App\Http\Controllers\Api\Admin\AnalyticsController::class, 'platform'])->name('admin.analytics.platform');
      Route::get('/sales', [\App\Http\Controllers\Api\Admin\AnalyticsController::class, 'sales'])->name('admin.analytics.sales');
      Route::get('/top-sellers', [\App\Http\Controllers\Api\Admin\AnalyticsController::class, 'topSellers'])->name('admin.analytics.top-sellers');
      Route::get('/top-products', [\App\Http\Controllers\Api\Admin\AnalyticsController::class, 'topProducts'])->name('admin.analytics.top-products');
      Route::get('/category-revenue', [\App\Http\Controllers\Api\Admin\AnalyticsController::class, 'categoryRevenue'])->name('admin.analytics.category-revenue');
    });

    // Admin Shop Verification
    Route::get('/shops/pending', [AdminShopController::class, 'pending'])->name('admin.shops.pending');
    Route::patch('/shops/{shop}/verify', [AdminShopController::class, 'verify'])->name('admin.shops.verify');
    Route::patch('/shops/{shop}/reject', [AdminShopController::class, 'reject'])->name('admin.shops.reject');

    // Admin User Management
    Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::patch('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('admin.users.suspend');
    Route::patch('/users/{user}/activate', [AdminUserController::class, 'activate'])->name('admin.users.activate');

    // Admin Payment Settings
    Route::get('/payment-settings', [\App\Http\Controllers\Api\Admin\PaymentSettingController::class, 'show'])->name('admin.payment-settings.show');
    Route::post('/payment-settings', [\App\Http\Controllers\Api\Admin\PaymentSettingController::class, 'store'])->name('admin.payment-settings.store');
    Route::put('/payment-settings/{paymentSetting}', [\App\Http\Controllers\Api\Admin\PaymentSettingController::class, 'update'])->name('admin.payment-settings.update');

    Route::get('/admin/orders', [\App\Http\Controllers\Api\Admin\OrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/admin/orders/{order}', [\App\Http\Controllers\Api\Admin\OrderController::class, 'show'])->name('admin.orders.show');
  });

  // Public shop route comes after the specific /shops/* routes above so it
  // cannot capture /shops/my-shop or /shops/pending.
  Route::get('/shops/{slug}', [ShopController::class, 'showPublic'])->name('shops.public');

  // Order Routes (Buyer only)
  Route::middleware(['auth:sanctum', 'buyer', 'throttle:api', 'sanitize'])->prefix('orders')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\Buyer\OrderController::class, 'index'])->name('orders.index');
    Route::get('/{order}', [\App\Http\Controllers\Api\Buyer\OrderController::class, 'show'])->name('orders.show');
    Route::patch('/{order}/confirm', [\App\Http\Controllers\Api\Buyer\OrderController::class, 'confirm'])->name('orders.confirm');
    Route::patch('/{order}/cancel', [\App\Http\Controllers\Api\Buyer\OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/{order}/cod-confirm', [\App\Http\Controllers\Api\Buyer\OrderController::class, 'codConfirm'])->name('orders.cod-confirm');
  });

  // Order Routes (Seller only)
  Route::middleware(['auth:sanctum', 'seller', 'throttle:api', 'sanitize'])->prefix('seller/orders')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\Seller\OrderController::class, 'index'])->name('seller.orders.index');
    Route::get('/{order}', [\App\Http\Controllers\Api\Seller\OrderController::class, 'show'])->name('seller.orders.show');
    Route::patch('/{order}/process', [\App\Http\Controllers\Api\Seller\OrderController::class, 'process'])->name('seller.orders.process');
    Route::patch('/{order}/ship', [\App\Http\Controllers\Api\Seller\OrderController::class, 'ship'])->name('seller.orders.ship');
  });

  // Analytics Routes (Seller only)
  Route::middleware(['auth:sanctum', 'seller', 'throttle:api', 'sanitize'])->prefix('seller/analytics')->group(function () {
    Route::get('/overview', [\App\Http\Controllers\Api\Seller\AnalyticsController::class, 'overview'])->name('seller.analytics.overview');
    Route::get('/sales', [\App\Http\Controllers\Api\Seller\AnalyticsController::class, 'sales'])->name('seller.analytics.sales');
    Route::get('/products/top', [\App\Http\Controllers\Api\Seller\AnalyticsController::class, 'topProducts'])->name('seller.analytics.products.top');
    Route::get('/products/low-stock', [\App\Http\Controllers\Api\Seller\AnalyticsController::class, 'lowStockProducts'])->name('seller.analytics.products.low-stock');
  });
});
