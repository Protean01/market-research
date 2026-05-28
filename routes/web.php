<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminAudienceController;
use App\Http\Controllers\Admin\AdminClientController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminExportController;
use App\Http\Controllers\Admin\AdminFraudController;
use App\Http\Controllers\Admin\AdminMonitoringController;
use App\Http\Controllers\Admin\AdminQualityController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\AdminSurveyController;
use App\Http\Controllers\Admin\AdminSurveyImageController;
use App\Http\Controllers\Admin\AdminTargetingController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\QuestionBankController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationFeedController;
use App\Http\Controllers\PrizeDrawController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [AuthController::class, 'show'])->name('home');
Route::get('/onboarding', [AuthController::class, 'show'])->name('onboarding');

// Legacy aliases kept for previously built frontend bundles
Route::post('/otp/request', [AuthController::class, 'sendOtp'])
    ->middleware(['throttle:60,1'])
    ->name('otp.request');
Route::post('/otp/verify', [AuthController::class, 'verifyOtp'])
    ->middleware(['throttle:60,1'])
    ->name('otp.verify');

// Current OTP endpoints used by the app
Route::post('/auth/otp/send', [AuthController::class, 'sendOtp'])
    ->middleware(['throttle:60,1'])
    ->name('auth.otp.send');
Route::post('/auth/otp/verify', [AuthController::class, 'verifyOtp'])
    ->middleware(['throttle:60,1'])
    ->name('auth.otp.verify');

Route::post('/auth/login-password', [AuthController::class, 'loginWithPassword'])
    ->name('auth.login-password');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Admin authentication — no auth middleware, accessible before login
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.store')->middleware('throttle:5,1');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
});

Route::middleware(['auth', 'phone.verified', 'password.set'])->group(function () {
    Route::get('/auth/create-password', fn () => Inertia::render('auth/Onboarding', [
        'step'                 => 4,
        'phone'                => auth()->user()?->phone_number,
        'must_create_password' => true,
    ]))->name('auth.create-password');
    Route::post('/auth/set-password', [AuthController::class, 'setPassword'])->name('auth.set-password');
    Route::get('/notifications/feed', [NotificationFeedController::class, 'index'])->name('notifications.feed');
    Route::post('/notifications/read', [NotificationFeedController::class, 'markRead'])->name('notifications.read');
    Route::get('/notifications', fn () => Inertia::render('Notifications'))->name('notifications.page');

    // Profile Setup (Exempt from completion check)
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.market-research.show');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.market-research.update');

    // Core Platform Features
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/surveys', [SurveyController::class, 'index'])->name('surveys');
    Route::get('/surveys/json', [SurveyController::class, 'available'])->name('surveys.json');
    Route::get('/surveys/{survey}', [SurveyController::class, 'show'])->name('survey');
    Route::post('/surveys/{survey}/submit', [SurveyController::class, 'submit'])
        ->middleware(['throttle:120,1', 'device.velocity'])
        ->name('survey.submit');
    Route::post('/surveys/{survey}/prize-draw', [PrizeDrawController::class, 'enter'])
        ->middleware(['throttle:5,1']) // Max 5 entries per minute
        ->name('survey.prize-draw');
    Route::get('/surveys/{survey}/slot-machine', [PrizeDrawController::class, 'slotMachine'])->name('survey.slot-machine');
    Route::post('/surveys/{survey}/spin', [PrizeDrawController::class, 'spin'])
        ->middleware(['throttle:3,1']) // Max 3 spins per minute (prevents abuse)
        ->name('survey.spin');

    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet');
    Route::get('/wallet/json', [WalletController::class, 'json'])->name('wallet.json');
    Route::get('/wallet/balance', [WalletController::class, 'balance'])->name('wallet.balance');
    Route::post('/wallet/redeem', [WalletController::class, 'redeem'])
        ->middleware(['throttle:10,1']) // Max 10 redemption attempts per minute
        ->name('wallet.redeem');
    Route::post('/wallet/draw', [WalletController::class, 'enterDraw'])
        ->middleware(['throttle:5,1']) // Max 5 draw entries per minute
        ->name('wallet.draw');

    Route::post('/notifications/subscribe', [NotificationController::class, 'subscribe'])->name('notifications.subscribe');
});

Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
    // Admin Core
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/audience', [AdminAudienceController::class, 'index'])->name('audience');
    Route::post('/dashboard/reconcile', [AdminDashboardController::class, 'reconcile'])->name('dashboard.reconcile');

    // Survey Management
    Route::get('/surveys', [AdminSurveyController::class, 'index'])->name('surveys');
    Route::post('/surveys', [AdminSurveyController::class, 'store'])->name('surveys.store');
    Route::put('/surveys/{survey}', [AdminSurveyController::class, 'update'])->name('surveys.update');
    Route::delete('/surveys/{survey}', [AdminSurveyController::class, 'destroy'])->name('surveys.destroy');
    Route::post('/surveys/{survey}/toggle', [AdminSurveyController::class, 'toggleStatus'])->name('surveys.toggle');
    Route::post('/surveys/{survey}/clone', [AdminSurveyController::class, 'clone'])->name('surveys.clone');
    Route::post('/surveys/{survey}/draw', [PrizeDrawController::class, 'draw'])->name('surveys.draw');
    Route::post('/surveys/{survey}/start-draw', [AdminSurveyController::class, 'startDrawPhase'])->name('surveys.start-draw');
    Route::post('/surveys/{survey}/export-question', [AdminSurveyController::class, 'exportQuestionToBank'])->name('surveys.export-question');
    Route::post('/survey-images', [AdminSurveyImageController::class, 'upload'])->name('surveys.images.upload');
    Route::get('/surveys/{survey}/report', [AdminReportController::class, 'show'])->name('surveys.report');
    Route::patch('/surveys/{survey}/entries/{entry}/deliver', [AdminReportController::class, 'markDelivered'])->name('surveys.entries.deliver');
    Route::post('/surveys/{survey}/close-draw', [AdminReportController::class, 'closeDrawPhase'])->name('surveys.close-draw');
    Route::get('/surveys/preview/reach', [AdminSurveyController::class, 'previewReach'])->name('surveys.preview');

    // Unified Question Library (Bank + Clients)
    Route::get('/question-bank', [QuestionBankController::class, 'index'])->name('question-bank.index');
    Route::post('/question-bank/templates', [QuestionBankController::class, 'storeTemplate'])->name('question-bank.store');
    Route::delete('/question-bank/templates/{template}', [QuestionBankController::class, 'destroyTemplate'])->name('question-bank.destroy');
    Route::get('/api/question-bank', [QuestionBankController::class, 'api'])->name('question-bank.api');

    Route::post('/question-bank/clients', [AdminClientController::class, 'store'])->name('clients.store');
    Route::put('/question-bank/clients/{client}', [AdminClientController::class, 'update'])->name('clients.update');
    Route::delete('/question-bank/clients/{client}', [AdminClientController::class, 'destroy'])->name('clients.destroy');
    Route::get('/api/clients', [AdminClientController::class, 'api'])->name('clients.api');

    // Redirect old clients route to unified library
    Route::get('/clients', fn () => redirect()->route('admin.question-bank.index'))->name('clients.index');

    // Targeting
    Route::get('/targeting', [AdminTargetingController::class, 'index'])->name('targeting');
    Route::post('/targeting/estimate', [AdminTargetingController::class, 'estimate'])->name('targeting.estimate');
    Route::put('/targeting/{survey}', [AdminTargetingController::class, 'update'])->name('targeting.update');

    // Monitoring & Fraud
    Route::get('/monitoring', [AdminMonitoringController::class, 'index'])->name('monitoring');
    Route::get('/quality', [AdminQualityController::class, 'index'])->name('quality.index');
    Route::post('/quality/user/{user}/suspend', [AdminQualityController::class, 'suspendUser'])->name('quality.suspend');
    Route::post('/quality/user/{user}/reinstate', [AdminQualityController::class, 'reinstateUser'])->name('quality.reinstate');
    Route::delete('/quality/response/{response}', [AdminQualityController::class, 'deleteResponse'])->name('quality.delete-response');
    Route::get('/fraud', [AdminFraudController::class, 'index'])->name('fraud');

    // User Management
    Route::get('/users', [AdminUserController::class, 'index'])->name('users');
    Route::post('/users/{user}/toggle', [AdminUserController::class, 'toggleStatus'])->name('users.toggle');
    Route::post('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{id}/restore', [AdminUserController::class, 'restore'])->name('users.restore');

    // Exports
    Route::get('/export', [AdminExportController::class, 'index'])->name('export');
    Route::get('/export/responses', [AdminExportController::class, 'exportResponses'])->middleware('throttle:10,1')->name('export.responses');
    Route::get('/export/transactions', [AdminExportController::class, 'exportTransactions'])->middleware('throttle:10,1')->name('export.transactions');
    Route::get('/export/prize-entries', [AdminExportController::class, 'exportPrizeEntries'])->middleware('throttle:10,1')->name('export.prize-entries');

    // Settings
    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');
});

require __DIR__.'/settings.php';
