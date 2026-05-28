<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\Response;
use App\Models\Survey;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // 1. Executive Summary Metrics
        $totalUsers = User::count();
        $newUsers7d = User::where('created_at', '>=', now()->subDays(7))->count();
        $newUsersToday = User::whereDate('created_at', today())->count();
        $activeUserCount = User::whereHas('responses', function ($q) {
            $q->where('completed_at', '>=', now()->subDays(30));
        })->count();

        $activeSurveys = Survey::where('is_active', true)->count();
        $totalResponses = Response::count();
        $totalResponses7d = Response::where('created_at', '>=', now()->subDays(7))->count();
        $totalResponsesToday = Response::whereDate('created_at', today())->count();

        // 2. Financial/Reward Summary
        $totalPointsInWild = Wallet::sum('points');
        $totalRedeemedPoints = WalletTransaction::where('type', 'redeem')->sum('points');
        $liabilityZmw = round($totalPointsInWild / 10, 2);
        $totalPayoutZmw = round($totalRedeemedPoints / 10, 2);

        // 3. Quality & Operational Summary
        $avgTrustScore = Profile::avg('trust_score') ?? 100;
        $flaggedResponseCount = Response::where('is_flagged', true)->count();
        $flaggedToday = Response::where('is_flagged', true)->whereDate('created_at', today())->count();
        $avgTimeTaken = Response::whereNotNull('time_taken')->avg('time_taken') ?? 0;

        // 4. Demographic Snapshot
        $locationDist = Profile::select('location', DB::raw('count(*) as count'))
            ->whereNotNull('location')
            ->groupBy('location')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        $incomeDist = Profile::select('income_band', DB::raw('count(*) as count'))
            ->whereNotNull('income_band')
            ->groupBy('income_band')
            ->orderByDesc('count')
            ->get();

        // 5. Surveys nearing cap (>= 80% full, still active)
        $surveysNearingCap = Survey::where('is_active', true)
            ->where('response_cap', '>', 0)
            ->whereRaw('response_count >= (response_cap * 0.8)')
            ->get(['id', 'title', 'response_count', 'response_cap']);

        // 6. Active prize draws
        $activeDraws = Survey::where('draw_phase_active', true)
            ->withCount(['prizeDrawEntries', 'prizeDrawEntries as winner_count' => function ($q) {
                $q->where('is_winner', true);
            }])
            ->get(['id', 'title', 'prize_name', 'prize_draw_enabled']);

        // --- Charts (Last 30 Days) ---
        $days = 30;
        $last30Days = Carbon::now()->subDays($days)->startOfDay();
        $driver = DB::connection()->getDriverName();
        $dateFunc = $driver === 'sqlite' ? "strftime('%Y-%m-%d', created_at)" : 'DATE(created_at)';

        $userGrowthRaw = User::select(DB::raw("$dateFunc as date"), DB::raw('count(*) as count'))
            ->where('created_at', '>=', $last30Days)
            ->groupBy('date')
            ->pluck('count', 'date');

        $responseGrowthRaw = Response::select(DB::raw("$dateFunc as date"), DB::raw('count(*) as count'))
            ->where('created_at', '>=', $last30Days)
            ->groupBy('date')
            ->pluck('count', 'date');

        $userGrowth = [];
        $responseGrowth = [];
        for ($i = $days; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $userGrowth[] = ['date' => $date, 'count' => $userGrowthRaw[$date] ?? 0];
            $responseGrowth[] = ['date' => $date, 'count' => $responseGrowthRaw[$date] ?? 0];
        }

        $topSurveys = Survey::withCount('responses')
            ->orderByDesc('responses_count')
            ->take(5)
            ->get();

        return Inertia::render('admin/AdminDashboard', [
            'summary' => [
                'users' => [
                    'total' => $totalUsers,
                    'growth_7d' => $newUsers7d,
                    'new_today' => $newUsersToday,
                    'active_30d' => $activeUserCount,
                    'active_rate' => $totalUsers > 0 ? round(($activeUserCount / $totalUsers) * 100) : 0,
                ],
                'surveys' => [
                    'active' => $activeSurveys,
                    'total_responses' => $totalResponses,
                    'responses_7d' => $totalResponses7d,
                    'responses_today' => $totalResponsesToday,
                    'avg_time' => round($avgTimeTaken),
                ],
                'financial' => [
                    'points_wild' => (int) $totalPointsInWild,
                    'liability_zmw' => $liabilityZmw,
                    'total_payout_zmw' => $totalPayoutZmw,
                ],
                'quality' => [
                    'avg_trust' => round($avgTrustScore),
                    'flagged_total' => $flaggedResponseCount,
                    'flagged_today' => $flaggedToday,
                ],
                'demographics' => [
                    'locations' => $locationDist,
                    'income' => $incomeDist,
                ],
            ],
            'charts' => [
                'users' => $userGrowth,
                'responses' => $responseGrowth,
            ],
            'top_surveys' => $topSurveys,
            'surveys_nearing_cap' => $surveysNearingCap,
            'active_draws' => $activeDraws,
            'trends' => [
                'user_growth_7d' => $newUsers7d,
            ],
        ]);
    }

    public function reconcile()
    {
        return DB::transaction(function () {
            $wallets = Wallet::lockForUpdate()->get();
            $updatedCount = 0;

            foreach ($wallets as $wallet) {
                // Only count completed transactions to determine actual balance
                $actualBalance = WalletTransaction::where('wallet_id', $wallet->id)
                    ->where('status', 'completed')
                    ->select(DB::raw("SUM(CASE WHEN type = 'earn' THEN points ELSE -points END) as balance"))
                    ->value('balance') ?? 0;

                if ($wallet->points != $actualBalance) {
                    $wallet->update(['points' => $actualBalance]);
                    $updatedCount++;
                }
            }

            return redirect()->back()->with('success', "Reconciliation complete. {$updatedCount} wallets adjusted.");
        });
    }
}
