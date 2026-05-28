<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Response;
use App\Models\Survey;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class AdminMonitoringController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $activeSurveysCount = Survey::where('is_active', true)->count();
        $responsesToday = Response::whereDate('created_at', Carbon::today())->count();
        // WalletTransaction only supports 'earn'/'redeem' with a 'points' field
        $totalPointsDistributed = WalletTransaction::where('type', 'earn')->sum('points');

        $activeSurveys = Survey::where('is_active', true)
            ->with(['responses' => function ($query) {
                $query->select('id', 'survey_id', 'answers');
            }])
            ->withCount('responses')
            ->get(['id', 'title', 'response_cap', 'response_count', 'created_at', 'questions']);

        // Attach simple chart data to each survey
        $activeSurveys->each(function ($survey) {
            $chartData = [];
            if ($survey->questions && is_array($survey->questions)) {
                foreach ($survey->questions as $index => $question) {
                    $qId = $question['id'] ?? $index;
                    if (in_array($question['type'], ['mcq', 'scale', 'checkbox', 'MCQ'])) {
                        $counts = [];
                        foreach ($survey->responses as $response) {
                            if (isset($response->answers[$qId])) {
                                $ans = $response->answers[$qId];
                                // Handle array answers (checkbox) vs string/number (mcq/scale)
                                $ansArray = is_array($ans) ? $ans : [$ans];
                                foreach ($ansArray as $a) {
                                    $counts[$a] = ($counts[$a] ?? 0) + 1;
                                }
                            }
                        }
                        $chartData[$qId] = [
                            'question' => $question['text'],
                            'type' => strtolower($question['type']),
                            'labels' => array_keys($counts),
                            'data' => array_values($counts),
                        ];
                    }
                }
            }
            $survey->chart_data = $chartData;
            // Remove responses array so we don't send too much raw data to frontend
            $survey->unsetRelation('responses');
        });

        return Inertia::render('admin/AdminMonitoring', [
            'metrics' => [
                'total_users' => $totalUsers,
                'active_surveys' => $activeSurveysCount,
                'responses_today' => $responsesToday,
                'total_points_distributed' => (int) $totalPointsDistributed,
            ],
            'active_surveys' => $activeSurveys,
        ]);
    }
}
