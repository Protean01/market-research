<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\Response;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminQualityController extends Controller
{
    public function index()
    {
        // 1. Flagged Responses (Recent)
        $flaggedResponses = Response::with(['user', 'survey'])
            ->where('is_flagged', true)
            ->latest('completed_at')
            ->take(50)
            ->get();

        // 2. Low Trust Users (< 70%)
        $lowTrustUsers = Profile::with('user')
            ->where('trust_score', '<', 70)
            ->orderBy('trust_score', 'asc')
            ->take(50)
            ->get();

        // 3. System Health Stats
        $totalResponses = Response::count();
        $totalFlagged = Response::where('is_flagged', true)->count();
        $flagRate = $totalResponses > 0 ? round(($totalFlagged / $totalResponses) * 100, 2) : 0;

        return Inertia::render('admin/AdminQuality', [
            'flagged_responses' => $flaggedResponses,
            'low_trust_users' => $lowTrustUsers,
            'stats' => [
                'total_flagged' => $totalFlagged,
                'flag_rate' => $flagRate,
                'avg_trust' => round(Profile::avg('trust_score') ?? 100),
            ],
        ]);
    }

    public function suspendUser(Request $request, User $user)
    {
        $user->update(['is_active' => false]);

        return back()->with('success', "User {$user->phone_number} has been suspended.");
    }

    public function reinstateUser(Request $request, User $user)
    {
        $user->update(['is_active' => true]);
        // Give them a small trust bump to start fresh
        if ($user->profile) {
            $user->profile->update(['trust_score' => 70]);
        }

        return back()->with('success', "User {$user->phone_number} has been reinstated.");
    }

    public function deleteResponse(Request $request, Response $response)
    {
        $survey = $response->survey;
        $response->delete();

        // Decrement survey response count to make room for a real answer
        if ($survey) {
            $survey->decrement('response_count');
        }

        return back()->with('success', 'Flagged response deleted and survey capacity restored.');
    }
}
