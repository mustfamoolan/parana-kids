<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Mobile\Delegate\MobileDelegateAuthController;
use App\Http\Controllers\Mobile\Delegate\MobileDelegateProductController;
use App\Http\Controllers\Mobile\Delegate\MobileDelegateOrderController;
use App\Http\Controllers\Mobile\Delegate\MobileDelegateCartController;
use App\Http\Controllers\Mobile\Delegate\MobileDelegateChatController;
use App\Http\Controllers\Mobile\Delegate\MobileDelegateNotificationController;
use App\Http\Controllers\Mobile\Delegate\MobileDelegateProductLinkController;
use App\Http\Controllers\Mobile\Delegate\MobileDelegateAlWaseetController;
use App\Http\Controllers\Mobile\Delegate\MobileDelegateDashboardController;
use App\Http\Controllers\Mobile\Admin\MobileAdminAuthController;
use App\Http\Controllers\Mobile\Admin\MobileAdminAlWaseetController;
use App\Http\Controllers\Mobile\Admin\MobileAdminOrderController;
use App\Http\Controllers\Mobile\Admin\MobileAdminOrderMovementController;
use App\Http\Controllers\Mobile\Admin\MobileAdminBulkReturnController;
use App\Http\Controllers\Mobile\Admin\MobileAdminBulkExchangeReturnController;

/*
|--------------------------------------------------------------------------
| Mobile API Routes
|--------------------------------------------------------------------------
|
| مسارات API منفصلة لتطبيقات الموبايل
| هذه المسارات منفصلة تماماً عن النظام الحالي
|
*/

// APIs المندوب (تطبيق موبايل المندوبين)
Route::prefix('delegate/auth')->group(function () {
    // تسجيل الدخول (عام - بدون مصادقة)
    Route::post('/login', [MobileDelegateAuthController::class, 'login']);

    // المسارات المحمية (تحتاج token)
    Route::middleware('auth.pwa')->group(function () {
        Route::get('/me', [MobileDelegateAuthController::class, 'me']);
        Route::post('/logout', [MobileDelegateAuthController::class, 'logout']);
        Route::put('/profile', [MobileDelegateAuthController::class, 'updateProfile']);
    });
});

// APIs المنتجات للمندوب (تحتاج token)
Route::prefix('delegate/products')->middleware('auth.pwa')->group(function () {
    Route::get('/', [MobileDelegateProductController::class, 'index']);
    Route::get('/{id}', [MobileDelegateProductController::class, 'show']);
});

// APIs الطلبات للمندوب (تحتاج token)
Route::prefix('delegate/orders')->middleware('auth.pwa')->group(function () {
    // مسارات خاصة (يجب أن تكون قبل المسارات العامة)
    Route::post('/initialize', [MobileDelegateCartController::class, 'initialize']);
    Route::post('/submit', [MobileDelegateCartController::class, 'submit']);

    // مسارات عامة
    Route::get('/', [MobileDelegateOrderController::class, 'index']);
    Route::get('/{id}', [MobileDelegateOrderController::class, 'show']);
    Route::put('/{id}', [MobileDelegateOrderController::class, 'update']);
    Route::delete('/{id}', [MobileDelegateOrderController::class, 'destroy']);
    Route::post('/{id}/restore', [MobileDelegateOrderController::class, 'restore']);
    Route::post('/{id}/force-delete', [MobileDelegateOrderController::class, 'forceDelete']);
});

// APIs السلة للمندوب (تحتاج token)
Route::prefix('delegate/carts')->middleware('auth.pwa')->group(function () {
    Route::get('/current', [MobileDelegateCartController::class, 'getCurrent']);
    Route::post('/items', [MobileDelegateCartController::class, 'addItems']);
    Route::put('/items/{id}', [MobileDelegateCartController::class, 'updateItem']);
    Route::delete('/items/{id}', [MobileDelegateCartController::class, 'removeItem']);
});

// APIs الرسائل للمندوب (تحتاج token)
Route::prefix('delegate/chat')->middleware('auth.pwa')->group(function () {
    Route::get('/conversations', [MobileDelegateChatController::class, 'getConversations']);
    Route::post('/conversation', [MobileDelegateChatController::class, 'getOrCreateConversation']);
    Route::get('/messages', [MobileDelegateChatController::class, 'getMessages']);
    Route::post('/send', [MobileDelegateChatController::class, 'sendMessage']);
    Route::post('/send-to-user', [MobileDelegateChatController::class, 'sendMessageToUser']);
    Route::post('/mark-read', [MobileDelegateChatController::class, 'markAsRead']);
    Route::get('/search-order', [MobileDelegateChatController::class, 'searchOrder']);
    Route::post('/send-order', [MobileDelegateChatController::class, 'sendOrderMessage']);
    Route::get('/search-product', [MobileDelegateChatController::class, 'searchProduct']);
    Route::post('/send-product', [MobileDelegateChatController::class, 'sendProductMessage']);
    Route::get('/users', [MobileDelegateChatController::class, 'getAvailableUsers']);
});

// APIs الإشعارات للمندوب (تحتاج token)
Route::prefix('delegate/notifications')->middleware('auth.pwa')->group(function () {
    Route::post('/register-token', [MobileDelegateNotificationController::class, 'registerToken']);
    Route::get('/', [MobileDelegateNotificationController::class, 'index']);
    Route::get('/unread-count', [MobileDelegateNotificationController::class, 'getUnreadCount']);
    Route::post('/{id}/mark-read', [MobileDelegateNotificationController::class, 'markAsRead']);
    Route::post('/mark-all-read', [MobileDelegateNotificationController::class, 'markAllAsRead']);
    Route::delete('/unregister-token', [MobileDelegateNotificationController::class, 'unregisterToken']);
    Route::post('/test', [MobileDelegateNotificationController::class, 'testNotification']);
    Route::get('/tokens-info', [MobileDelegateNotificationController::class, 'getTokensInfo']);
});

// APIs روابط المنتجات للمندوب (تحتاج token)
Route::prefix('delegate/product-links')->middleware('auth.pwa')->group(function () {
    Route::get('/', [MobileDelegateProductLinkController::class, 'index']);
    Route::post('/', [MobileDelegateProductLinkController::class, 'store']);
    Route::delete('/{id}', [MobileDelegateProductLinkController::class, 'destroy']);
    Route::get('/get-sizes', [MobileDelegateProductLinkController::class, 'getSizes']);
    Route::get('/warehouses', [MobileDelegateProductLinkController::class, 'getWarehouses']);
});

// APIs تتبع طلبات الوسيط للمندوب (تحتاج token)
Route::prefix('delegate/alwaseet')->middleware('auth.pwa')->group(function () {
    Route::get('/status-cards', [MobileDelegateAlWaseetController::class, 'getStatusCards']);
    Route::get('/orders', [MobileDelegateAlWaseetController::class, 'getOrders']);
    Route::get('/orders/{id}', [MobileDelegateAlWaseetController::class, 'getOrderDetails']);
});

// APIs الداشبورد للمندوب (تحتاج token)
Route::prefix('delegate/dashboard')->middleware('auth.pwa')->group(function () {
    Route::get('/', [MobileDelegateDashboardController::class, 'index']);
});

// APIs المدير/المجهز (تطبيق موبايل المدير والمجهز)
Route::prefix('admin/auth')->group(function () {
    // تسجيل الدخول (عام - بدون مصادقة)
    Route::post('/login', [MobileAdminAuthController::class, 'login']);

    // المسارات المحمية (تحتاج token)
    Route::middleware('auth.pwa')->group(function () {
        Route::get('/me', [MobileAdminAuthController::class, 'me']);
        Route::post('/logout', [MobileAdminAuthController::class, 'logout']);
        Route::put('/profile', [MobileAdminAuthController::class, 'updateProfile']);
    });
});

// APIs تتبع طلبات الوسيط للمدير والمجهز (تحتاج token)
Route::prefix('admin/alwaseet')->middleware('auth.pwa')->group(function () {
    Route::get('/status-cards', [MobileAdminAlWaseetController::class, 'getStatusCards']);
    Route::get('/orders', [MobileAdminAlWaseetController::class, 'getOrders']);
    Route::get('/orders/{id}', [MobileAdminAlWaseetController::class, 'getOrderDetails']);
});

// APIs إدارة الطلبات للمدير والمجهز (تحتاج token)
Route::prefix('admin/orders')->middleware('auth.pwa')->group(function () {
    // قوائم الفلاتر
    Route::get('/filter-options', [MobileAdminOrderController::class, 'getFilterOptions']);

    // قوائم الطلبات
    Route::get('/pending', [MobileAdminOrderController::class, 'getPendingOrders']);
    Route::get('/confirmed', [MobileAdminOrderController::class, 'getConfirmedOrders']);
    Route::get('/', [\App\Http\Controllers\Api\Admin\AdminOrderController::class, 'index']); // New unified API
    Route::get('/filters', [\App\Http\Controllers\Api\Admin\AdminOrderController::class, 'getFilters']); // New filters API

    // تفاصيل وتعديل الطلب
    Route::get('/{id}', [MobileAdminOrderController::class, 'getOrderDetails']);
    Route::get('/{id}/edit', [MobileAdminOrderController::class, 'getOrderEditData']);
    Route::put('/{id}', [MobileAdminOrderController::class, 'updateOrder']);
    Route::put('/{id}/quick-status', [MobileAdminOrderController::class, 'updateQuickStatus']);

    // تجهيز الطلب
    Route::get('/{id}/process', [MobileAdminOrderController::class, 'getOrderProcessData']);
    Route::post('/{id}/process', [MobileAdminOrderController::class, 'processOrder']);

    // المواد المطلوبة
    Route::get('/materials', [MobileAdminOrderController::class, 'getMaterialsList']);
    Route::get('/materials/grouped', [MobileAdminOrderController::class, 'getMaterialsListGrouped']);

    // الإرجاعات الجزئية
    Route::get('/partial-returns', [MobileAdminOrderController::class, 'getPartialReturns']);
    Route::get('/{id}/partial-return', [MobileAdminOrderController::class, 'getPartialReturnOrder']);
    Route::post('/{id}/partial-return', [MobileAdminOrderController::class, 'processPartialReturn']);

    // إنشاء طلب جديد (Admin Order Creation)
    Route::prefix('create')->group(function () {
        Route::post('/initialize', [\App\Http\Controllers\Api\Admin\AdminOrderCreationApiController::class, 'initialize']);
        Route::get('/current-cart', [\App\Http\Controllers\Api\Admin\AdminOrderCreationApiController::class, 'currentCart']);
        Route::post('/items', [\App\Http\Controllers\Api\Admin\AdminOrderCreationApiController::class, 'addItem']);
        Route::get('/search-filters', [\App\Http\Controllers\Api\Admin\AdminOrderCreationApiController::class, 'getSearchFilters']);
        Route::get('/search-products', [\App\Http\Controllers\Api\Admin\AdminOrderCreationApiController::class, 'searchProducts']);
        Route::put('/items/{itemId}', [\App\Http\Controllers\Api\Admin\AdminOrderCreationApiController::class, 'updateItem']);
        Route::delete('/items/{itemId}', [\App\Http\Controllers\Api\Admin\AdminOrderCreationApiController::class, 'removeItem']);
        Route::post('/submit', [\App\Http\Controllers\Api\Admin\AdminOrderCreationApiController::class, 'submit']);
    });
});

// APIs حركات الطلبات للمدير والمجهز (تحتاج token)
Route::prefix('admin/order-movements')->middleware('auth.pwa')->group(function () {
    Route::get('/', [MobileAdminOrderMovementController::class, 'getOrderMovements']);
    Route::get('/statistics', [MobileAdminOrderMovementController::class, 'getOrderMovementsStatistics']);
});

// APIs الإرجاعات الجماعية للمدير والمجهز (تحتاج token)
Route::prefix('admin/bulk-returns')->middleware('auth.pwa')->group(function () {
    Route::get('/filter-options', [MobileAdminBulkReturnController::class, 'getFilterOptions']);
    Route::get('/search-products', [MobileAdminBulkReturnController::class, 'searchProducts']);
    Route::post('/', [MobileAdminBulkReturnController::class, 'returnProducts']);
});

// APIs الإرجاعات/الاستبدالات الجماعية للمدير والمجهز (تحتاج token)
Route::prefix('admin/bulk-exchange-returns')->middleware('auth.pwa')->group(function () {
    Route::get('/filter-options', [MobileAdminBulkExchangeReturnController::class, 'getFilterOptions']);
    Route::get('/search-products', [MobileAdminBulkExchangeReturnController::class, 'searchProducts']);
    Route::post('/', [MobileAdminBulkExchangeReturnController::class, 'returnProducts']);
});
