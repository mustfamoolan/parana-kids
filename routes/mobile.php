<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Mobile\Delegate\MobileDelegateAuthController;
use App\Http\Controllers\Mobile\Delegate\MobileDelegateProductController;
use App\Http\Controllers\Mobile\Delegate\MobileDelegateOrderController;

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
    Route::get('/', [MobileDelegateOrderController::class, 'index']);
    Route::get('/{id}', [MobileDelegateOrderController::class, 'show']);
    Route::put('/{id}', [MobileDelegateOrderController::class, 'update']);
    Route::delete('/{id}', [MobileDelegateOrderController::class, 'destroy']);
    Route::post('/{id}/restore', [MobileDelegateOrderController::class, 'restore']);
    Route::post('/{id}/force-delete', [MobileDelegateOrderController::class, 'forceDelete']);
});

// APIs المدير/المجهز (لاحقاً - يمكن إضافتها هنا)
// Route::prefix('admin/auth')->group(function () {
//     Route::post('/login', [MobileAdminAuthController::class, 'login']);
//     ...
// });
