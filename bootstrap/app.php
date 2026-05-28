<?php

use App\Http\Middleware\AdminOnly;
use App\Http\Middleware\DeviceVelocityGuard;
use App\Http\Middleware\EnsurePasswordIsSet;
use App\Http\Middleware\EnsurePhoneVerified;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->validateCsrfTokens(except: app()->runningUnitTests() ? ['*'] : [
            'auth/otp/*',
            'otp/*',
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'phone.verified' => EnsurePhoneVerified::class,
            'admin' => AdminOnly::class,
            'device.velocity' => DeviceVelocityGuard::class,
            'password.set' => EnsurePasswordIsSet::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('onboarding'));

        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->is('auth/otp/*')) {
                Log::error('OTP Validation Error', [
                    'payload' => $request->all(),
                    'errors' => $e->errors(),
                ]);
            }
        });
    })->create();
