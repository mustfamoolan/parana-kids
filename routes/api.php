<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// SweetAlert Routes
Route::middleware('auth:web')->group(function () {
    Route::get('/sweet-alerts/unread', [App\Http\Controllers\SweetAlertController::class, 'getUnread']);
    Route::post('/sweet-alerts/{id}/read', [App\Http\Controllers\SweetAlertController::class, 'markAsRead']);
});
