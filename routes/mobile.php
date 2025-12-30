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
use App\Http\Controllers\Mobile\Admin\MobileAdminAuthController;

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
