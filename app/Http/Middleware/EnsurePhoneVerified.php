<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneVerified
{
    /**
     * Ensure the authenticated user has verified their phone number.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->phone_verified_at) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Phone verification required.'], 403);
            }

            return redirect()->route('onboarding');
        }

        return $next($request);
    }
}
