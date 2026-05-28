<?php

namespace App\Http\Controllers;

use AfricasTalking\SDK\AfricasTalking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function show()
    {
        if (Auth::check() && Auth::user()->phone_verified_at) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('auth/Onboarding');
    }

    private function sanitizePhone(string $phone): string
    {
        // Remove all non-numeric characters except +
        $numeric = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '+')) {
            return '+'.$numeric;
        }

        return $numeric;
    }

    public function sendOtp(Request $request)
    {
        Log::debug('sendOtp called', ['payload' => $request->all(), 'ip' => $request->ip()]);

        try {
            $data = $request->validate([
                'phone_number' => ['required', 'string', 'min:7', 'max:20'],
                'ignore_password' => ['nullable', 'boolean'],
            ]);
        } catch (ValidationException $e) {
            Log::warning('sendOtp validation failed', ['errors' => $e->errors()]);
            throw $e;
        }

        $phone = $this->sanitizePhone($data['phone_number']);
        $existing = User::where('phone_number', $phone)->first();

        if ($existing) {
            // Force a fresh copy from DB to avoid any stale state/casting issues
            $existing->refresh();
        }

        Log::debug('OTP/Password decision', [
            'phone' => $phone,
            'user_found' => (bool) $existing,
            'is_password_set_raw' => $existing ? $existing->getRawOriginal('is_password_set') : 'N/A',
            'is_password_set_cast' => $existing ? ($existing->is_password_set ? 'true' : 'false') : 'N/A',
            'ignore_password' => $request->input('ignore_password'),
        ]);

        if ($existing && $existing->is_active === false) {
            return response()->json(['error' => 'Your account has been suspended. Please contact support.'], 403);
        }

        // Explicitly check for boolean true; otherwise, default to password if set
        $wantsOtpFallback = filter_var($request->input('ignore_password'), FILTER_VALIDATE_BOOLEAN);

        if ($existing && $existing->is_password_set && ! $wantsOtpFallback) {
            return response()->json([
                'status' => 'require_password',
                'message' => 'Please enter your password to login.',
            ]);
        }

        // --- New user or OTP fallback: generate and send OTP ---
        $otp = random_int(100000, 999999);
        Cache::put('otp_'.$phone, $otp, now()->addMinutes(5));
        Log::info('OTP generated for '.$phone);

        if (config('app.log_otp')) {
            Log::debug('OTP generated with code', ['phone' => $phone, 'otp' => $otp]);
        }

        try {
            $username = config('services.africastalking.username');
            $key = config('services.africastalking.key');

            if ($username && $key) {
                $at = new AfricasTalking($username, $key);
                $at->sms()->send([
                    'to' => $phone,
                    'message' => "Your OTP: {$otp}",
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Unable to send OTP via Africa\'s Talking', ['error' => $e->getMessage()]);
        }

        return response()->json(['message' => 'OTP sent', 'status' => 'otp_sent']);
    }

    public function loginWithPassword(Request $request)
    {
        $data = $request->validate([
            'phone_number' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $phone = $this->sanitizePhone($data['phone_number']);

        Log::debug('Login attempt with password', ['phone' => $phone]);

        $user = User::where('phone_number', $phone)->first();

        if (! $user) {
            Log::warning('Login failed: User not found', ['phone' => $phone]);

            return response()->json(['error' => 'User not found.'], 422);
        }

        if ($user->is_active === false) {
            Log::warning('Login failed: Account suspended', ['phone' => $phone]);

            return response()->json(['error' => 'Your account has been suspended. Please contact support.'], 422);
        }

        if (! $user->is_password_set) {
            Log::warning('Login failed: Password not set', ['phone' => $phone]);

            return response()->json(['error' => 'Please log in with OTP and set a password first.'], 422);
        }

        if (! Hash::check($data['password'], $user->password)) {
            Log::warning('Login failed: Incorrect password', ['phone' => $phone]);

            return response()->json(['error' => 'Invalid credentials.'], 422);
        }

        if (! $user->phone_verified_at) {
            $user->update(['phone_verified_at' => now()]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        Log::info('User logged in with password', ['user_id' => $user->id]);

        return response()->json(['status' => 'authenticated']);
    }

    public function verifyOtp(Request $request)
    {
        $data = $request->validate([
            'phone_number' => ['required', 'string'],
            'otp' => ['required'],
            'language' => ['nullable', 'string', 'max:50'],
        ]);

        $phone = $this->sanitizePhone($data['phone_number']);

        $cached = Cache::get('otp_'.$phone);

        if (! $cached || (string) $cached !== (string) $data['otp']) {
            return back()->withErrors(['error' => 'Invalid or expired OTP.']);
        }

        try {
            return DB::transaction(function () use ($phone, $data, $request) {
                $user = User::firstOrCreate(
                    ['phone_number' => $phone],
                    [
                        'name' => 'Member '.substr($phone, -4),
                        'email' => $this->placeholderEmail($phone),
                        'password' => Str::random(16), // cast 'hashed' auto-hashes this
                        'phone_verified_at' => now(),
                        'is_password_set' => false,
                    ],
                );

                if (! $user->wallet) {
                    $user->wallet()->create();
                }
                if (! $user->profile) {
                    $user->profile()->create(['language' => $data['language'] ?? 'English']);
                }

                $user->forceFill(['phone_verified_at' => now()])->save();
                Auth::login($user, true);

                Cache::forget('otp_'.$phone);

                // For AJAX (axios) callers, return JSON so the SPA can drive the flow.
                if ($request->wantsJson()) {
                    if (! $user->is_password_set) {
                        return response()->json([
                            'status' => 'require_password_creation',
                            'message' => 'Please create a password to continue.',
                            'phone' => $phone,
                        ]);
                    }

                    return response()->json(['status' => 'authenticated']);
                }

                // Fallback for non-AJAX requests: render onboarding or redirect.
                if (! $user->is_password_set) {
                    return Inertia::render('auth/Onboarding', [
                        'step' => 4,
                        'phone' => $phone,
                        'must_create_password' => true,
                    ]);
                }

                return redirect()->route('dashboard');
            });
        } catch (\Throwable $e) {
            Log::error('OTP Verification Transaction Failed', ['error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Verification failed. Please try again.']);
        }
    }

    public function setPassword(Request $request)
    {
        $data = $request->validate([
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ]);

        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        // Do NOT use Hash::make() here — the User model's 'hashed' cast handles
        // hashing automatically. Using Hash::make() would double-hash the value,
        // making login with this password impossible.
        $user->update([
            'password' => $data['password'],
            'is_password_set' => true,
        ]);

        Log::info('Password set successfully for user', ['user_id' => $user->id]);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function placeholderEmail(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);

        return \sprintf('user%s@mr.local', $clean ?: Str::random(6));
    }
}
