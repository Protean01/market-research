<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationFeedController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/otp/send', [AuthController::class, 'sendOtp'])->middleware(['throttle:30,1', 'device.velocity']);
Route::post('/auth/otp/verify', [AuthController::class, 'verifyOtp'])->middleware(['throttle:10,1', 'device.velocity']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/surveys/available', [SurveyController::class, 'available']);
    Route::post('/surveys/{survey}/submit', [SurveyController::class, 'submit']);

    Route::get('/wallet/balance', [WalletController::class, 'balance']);
    Route::post('/wallet/redeem', [WalletController::class, 'redeem']);

    Route::get('/profile/me', [ProfileController::class, 'me']);
    Route::post('/profile', [ProfileController::class, 'update']);

    Route::post('/notifications/subscribe', [NotificationController::class, 'subscribe']);
    Route::post('/notifications/read-all', [NotificationFeedController::class, 'markAllRead']);
    Route::get('/notifications', [NotificationFeedController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationFeedController::class, 'markRead']);
});
