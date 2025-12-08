<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// User API Routes
// تسجيل دخول المدير والمجهز
Route::post('/admin/login', [UserApiController::class, 'loginAdmin']);

// تسجيل دخول المندوب
Route::post('/delegate/login', [UserApiController::class, 'loginDelegate']);

// المسارات المحمية (تتطلب PWA token)
Route::middleware('auth.pwa')->group(function () {
    // معلومات المستخدم الحالي
    Route::get('/user', [UserApiController::class, 'me']);

    // تحديث بيانات المستخدم الحالي
    Route::put('/user', [UserApiController::class, 'update']);

    // إنشاء مستخدم جديد (للمدير فقط)
    Route::post('/admin/users', [UserApiController::class, 'store']);
});
