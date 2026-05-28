<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrizeDrawEntry;
use App\Models\Response;
use App\Models\Survey;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class AdminReportController extends Controller
{
    public function show(Survey $survey)
    {
        // Restriction for researchers
        if (! auth()->user()->isAdmin() && $survey->user_id !== auth()->id()) {
            abort(403);
        }

        $responses = Response::where('survey_id', $survey->id)->with('user')->get();

        $analytics = $this->calculateAnalytics($survey, $responses);

        $drawEntries  = collect();
        $spunCount    = 0;
        $totalPrizes  = 0;
        $winnersCount = 0;

        $isDrawSurvey = in_array($survey->reward_type, ['prize_draw', 'airtime'], true);
        if ($isDrawSurvey) {
            $drawEntries  = PrizeDrawEntry::where('survey_id', $survey->id)
                ->with(['user:id,name,phone_number'])
                ->latest()
                ->get();
            $spunCount    = $drawEntries->where('has_spun', true)->count();
            $totalPrizes  = \count($survey->prizes ?? []);
            $winnersCount = $drawEntries->where('is_winner', true)->count();
        }

        $responsesCollection = collect($responses);

        return Inertia::render('admin/SurveyReport', [
            'survey'       => $survey,
            'responseCount' => $responsesCollection->count(),
            'analytics'    => $analytics,
            'flaggedCount' => $responsesCollection->where('is_flagged', true)->count(),
            'averageTime'  => $responsesCollection->avg('time_taken'),
            'drawEntries'  => $drawEntries,
            'spunCount'    => $spunCount,
            'totalPrizes'  => $totalPrizes,
            'winners'      => $drawEntries->where('is_winner', true)->values(),
        ]);
    }

    public function markDelivered(Survey $survey, PrizeDrawEntry $entry): RedirectResponse
    {
        if (! auth()->user()->isAdmin() && $survey->user_id !== auth()->id()) {
            abort(403);
        }

        $entry->update([
            'prize_delivered' => true,
            'delivered_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Prize marked as delivered.');
    }

    public function closeDrawPhase(Survey $survey): RedirectResponse
    {
        if (! auth()->user()->isAdmin() && $survey->user_id !== auth()->id()) {
            abort(403);
        }

        $survey->update(['draw_phase_active' => false]);

        return redirect()->back()->with('success', 'Prize draw phase closed.');
    }

    private function calculateAnalytics(Survey $survey, $responses)
    {
        $questions = $survey->questions ?? [];
        $stats = [];

        foreach ($questions as $q) {
            $qId = $q['id'];
            $qType = $q['type'];

            $answers = $responses->pluck("answers.$qId")->filter();

            if ($qType === 'mcq' || $qType === 'checkbox' || $qType === 'scale') {
                $counts = $answers->flatten()->countBy();
                $stats[$qId] = [
                    'text' => $q['text'],
                    'type' => $qType,
                    'data' => $counts,
                ];
            } else {
                // For text questions, just show recent answers
                $stats[$qId] = [
                    'text' => $q['text'],
                    'type' => $qType,
                    'data' => $answers->take(10)->values(),
                ];
            }
        }

        return $stats;
    }
}
