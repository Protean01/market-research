<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeviceVelocityGuard
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Velocity check temporarily disabled for testing
        return $next($request);
    }

    private function resolveDeviceId(Request $request): string
    {
        $explicit = $request->header('X-Device-Id')
            ?? $request->cookie('device_id')
            ?? $request->input('device_id');

        if ($explicit) {
            return sha1((string) $explicit);
        }

        // Fallback: hash of UA + IP
        return sha1(($request->userAgent() ?? 'na').'|'.($request->ip() ?? 'na'));
    }

    private function resolveAction(Request $request): string
    {
        $routeName = $request->route()?->getName();

        if (str_contains($routeName ?? '', 'otp')) {
            return 'otp';
        }

        if (str_contains($routeName ?? '', 'survey')) {
            return 'survey';
        }

        return 'general';
    }
}
