<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Profile;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminAudienceController extends Controller
{
    public function index(Request $request)
    {
        $surveyId = $request->query('survey_id');
        $clientId = $request->query('client_id');

        $surveysQuery = Survey::orderByDesc('created_at');
        if ($clientId) {
            $surveysQuery->where('client_id', $clientId);
        }
        $surveys = $surveysQuery->get(['id', 'title', 'client_id']);

        $clients = Client::orderBy('name')->get(['id', 'name']);

        $query = Profile::query();

        if ($surveyId) {
            $query->whereIn('user_id', function ($q) use ($surveyId) {
                $q->select('user_id')
                    ->from('responses')
                    ->where('survey_id', $surveyId);
            });
            $totalUsers = DB::table('responses')->where('survey_id', $surveyId)->distinct('user_id')->count();
        } elseif ($clientId) {
            $query->whereIn('user_id', function ($q) use ($clientId) {
                $q->select('responses.user_id')
                    ->from('responses')
                    ->join('surveys', 'responses.survey_id', '=', 'surveys.id')
                    ->where('surveys.client_id', $clientId);
            });
            $totalUsers = DB::table('responses')
                ->join('surveys', 'responses.survey_id', '=', 'surveys.id')
                ->where('surveys.client_id', $clientId)
                ->distinct('responses.user_id')
                ->count();
        } else {
            $totalUsers = User::count();
        }

        $totalProfiles = $query->count();

        // 1. Gender Distribution
        $genderData = (clone $query)->select('gender', DB::raw('count(*) as count'))
            ->whereNotNull('gender')
            ->groupBy('gender')
            ->get();

        // 2. Age Band Distribution
        $ageData = (clone $query)->select('age_band', DB::raw('count(*) as count'))
            ->whereNotNull('age_band')
            ->groupBy('age_band')
            ->orderBy('age_band')
            ->get();

        // 3. Income Band
        $incomeData = (clone $query)->select('income_band', DB::raw('count(*) as count'))
            ->whereNotNull('income_band')
            ->groupBy('income_band')
            ->get();

        // 4. Occupation Top 5
        $occupationData = (clone $query)->select('occupation', DB::raw('count(*) as count'))
            ->whereNotNull('occupation')
            ->groupBy('occupation')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        // 5. Location Top 5
        $locationData = (clone $query)->select('location', DB::raw('count(*) as count'))
            ->whereNotNull('location')
            ->groupBy('location')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        // 6. Employment Distribution
        $employmentData = (clone $query)->select('employment', DB::raw('count(*) as count'))
            ->whereNotNull('employment')
            ->groupBy('employment')
            ->get();

        // 7. Education Distribution
        $educationData = (clone $query)->select('education', DB::raw('count(*) as count'))
            ->whereNotNull('education')
            ->groupBy('education')
            ->get();

        // 8. Custom Traits
        $profiles = (clone $query)->whereNotNull('enriched_attributes')->get(['enriched_attributes']);
        $traitCounts = [];
        foreach ($profiles as $p) {
            foreach ($p->enriched_attributes as $key => $val) {
                $traitCounts[$key] = ($traitCounts[$key] ?? 0) + 1;
            }
        }
        arsort($traitCounts);
        $topTraits = array_slice($traitCounts, 0, 10, true);

        return Inertia::render('admin/Audience', [
            'surveys' => $surveys,
            'clients' => $clients,
            'selected_survey_id' => $surveyId,
            'selected_client_id' => $clientId,
            'stats' => [
                'total_users' => $totalUsers,
                'profile_completion' => $totalUsers > 0 ? round(($totalProfiles / $totalUsers) * 100) : 0,
            ],
            'demographics' => [
                'gender' => $genderData,
                'age' => $ageData,
                'income' => $incomeData,
                'occupation' => $occupationData,
                'location' => $locationData,
                'employment' => $employmentData,
                'education' => $educationData,
            ],
            'traits' => $topTraits,
        ]);
    }
}
