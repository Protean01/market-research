<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EnsurePasswordIsSet
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && ! $user->is_password_set) {
            // Allow the set-password POST through so the form can actually save
            if ($request->routeIs('auth.set-password')) {
                return $next($request);
            }

            // JSON/API callers: tell the client to redirect
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Please set a password to continue.',
                    'redirect' => route('auth.create-password'),
                ], 403);
            }

            return Inertia::render('auth/Onboarding', [
                'step'                 => 4,
                'phone'                => $user->phone_number,
                'must_create_password' => true,
            ])->toResponse($request);
        }

        return $next($request);
    }
}
