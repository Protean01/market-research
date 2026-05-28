<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        \Log::info('Dashboard hit', ['user_id' => $request->user()?->id]);
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $wallet = $user->wallet ?? $user->wallet()->create();

        // Stats
        $responses = $user->responses();
        $surveysDoneCount = $responses->count();
        $totalEarned = $user->responses()->whereYear('created_at', now()->year)->sum('earned_points');
        $totalRedeemed = $wallet->transactions()->where('type', 'redeem')->sum('points');

        // Recent Activity
        $recentTransactions = $wallet->transactions()
            ->latest()
            ->take(5)
            ->get();

        // Ready to Spin Surveys
        $readyToSpin = Survey::where('draw_phase_active', true)
            ->whereHas('responses', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->whereHas('prizeDrawEntries', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('has_spun', false);
            })
            ->get();

        // Featured/Available Surveys
        $completedIds = $responses->pluck('survey_id');

        $featuredSurveys = Survey::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('response_cap')
                    ->orWhere('response_cap', 0)
                    ->orWhereColumn('response_count', '<', 'response_cap');
            })
            ->whereNotIn('id', $completedIds)
            ->latest()
            ->take(3)
            ->get();

        $profile = $user->profile;

        return Inertia::render('Dashboard', [
            'stats' => [
                'surveys_done' => $surveysDoneCount,
                'total_earned' => (int) $totalEarned,
                'total_redeemed' => (int) $totalRedeemed,
                'current_points' => $wallet->points,
                'current_streak' => $profile?->current_streak ?? 0,
                'longest_streak' => $profile?->longest_streak ?? 0,
            ],
            'recent_activity' => $recentTransactions,
            'featured_surveys' => $featuredSurveys,
            'ready_to_spin' => $readyToSpin,
        ]);
    }
}
