<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\Api\MessageApiController;
use App\Http\Controllers\Api\DelegateProductApiController;
use App\Http\Controllers\Api\MaterialApiController;

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

    // Messages API Routes
    // جلب قائمة المحادثات
    Route::get('/messages/conversations', [MessageApiController::class, 'getConversations']);

    // الحصول على أو إنشاء محادثة
    Route::post('/messages/conversation', [MessageApiController::class, 'getOrCreateConversation']);

    // جلب الرسائل
    Route::get('/messages/{conversation_id}', [MessageApiController::class, 'getMessages']);

    // إرسال رسالة
    Route::post('/messages', [MessageApiController::class, 'sendMessage']);

    // إرسال رسالة لمستخدم
    Route::post('/messages/send-to-user', [MessageApiController::class, 'sendMessageToUser']);

    // تحديد الرسائل كمقروءة
    Route::post('/messages/mark-read', [MessageApiController::class, 'markAsRead']);

    // البحث عن طلب
    Route::get('/messages/search/order', [MessageApiController::class, 'searchOrder']);

    // إرسال رسالة مع طلب
    Route::post('/messages/order', [MessageApiController::class, 'sendOrderMessage']);

    // البحث عن منتج
    Route::get('/messages/search/product', [MessageApiController::class, 'searchProduct']);

    // إرسال رسالة مع منتج
    Route::post('/messages/product', [MessageApiController::class, 'sendProductMessage']);

    // إنشاء مجموعة (للمدير فقط)
    Route::post('/messages/groups', [MessageApiController::class, 'createGroup']);

    // إضافة مشاركين للمجموعة (للمدير فقط)
    Route::post('/messages/groups/{id}/participants', [MessageApiController::class, 'addParticipantsToGroup']);

    // إزالة مشارك من المجموعة (للمدير فقط)
    Route::delete('/messages/groups/{id}/participants/{user_id}', [MessageApiController::class, 'removeParticipantFromGroup']);

    // جلب قائمة المشاركين في المجموعة
    Route::get('/messages/groups/{id}/participants', [MessageApiController::class, 'getGroupParticipants']);

    // Delegate Products API Routes
    // جلب المنتجات للمندوب
    Route::get('/delegate/products', [DelegateProductApiController::class, 'index']);

    // Materials API (Raw & Grouped)
    Route::get('/materials/raw', [MaterialApiController::class, 'index']);
    Route::get('/materials/grouped', [MaterialApiController::class, 'grouped']);
});
