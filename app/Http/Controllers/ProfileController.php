<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return Inertia::render('Profile', [
            'profile' => $request->user()->profile,
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('profile');

        return response()->json($user);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'birth_year' => ['nullable', 'integer', 'min:1940', 'max:'.date('Y')],
            'age_band' => ['nullable', 'string'],
            'gender' => ['nullable', 'string'],
            'location' => ['nullable', 'string'],
            'employment' => ['nullable', 'string'],
            'income_band' => ['nullable', 'string'],
            'marital_status' => ['nullable', 'string'],
            'education' => ['nullable', 'string'],
            'occupation' => ['nullable', 'string'],
            'food_preference' => ['nullable', 'string'],
            'enriched_attributes' => ['nullable', 'array'],
        ]);

        return \DB::transaction(function () use ($request, $data) {
            $user = $request->user();
            $profile = $user->profile ?? $user->profile()->create();

            $wasIncomplete = ! $profile->is_complete;

            $profile->fill($data);
            $profile->save();

            $message = 'Profile updated';

            if ($wasIncomplete && $profile->calculateCompletionPercentage() >= 99) {
                // Ensure it's marked complete if the model boot method didn't catch it
                if (! $profile->is_complete) {
                    $profile->is_complete = true;
                    $profile->save();
                }

                $wallet = $user->wallet ?? $user->wallet()->create();
                $bonusPoints = 50;
                $wallet->increment('points', $bonusPoints);

                $wallet->transactions()->create([
                    'type' => 'earn',
                    'points' => $bonusPoints,
                    'meta' => ['survey_id' => null, 'reason' => 'Profile Completion Bonus'],
                ]);

                $message = 'Profile updated. You earned a 50 pts welcome bonus!';
            }

            return $request->expectsJson() && ! $request->header('X-Inertia')
                ? response()->json(['message' => $message, 'profile' => $profile])
                : back()->with('status', $message);
        });
    }
}
