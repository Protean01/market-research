<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\QuestionTemplate;
use App\Models\Survey;
use App\Models\SurveyNotificationLog;
use App\Models\User;
use App\Notifications\NewSurveyNotification;
use App\Notifications\PrizeDrawInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class AdminSurveyController extends Controller
{
    public function index()
    {
        $query = Survey::withCount('responses')
            ->orderByDesc('created_at');

        if (! auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }

        $surveys = $query->get();

        return Inertia::render('admin/AdminSurveys', [
            'surveys' => $surveys,
            'clients' => Client::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'reward_amount' => ['nullable', 'numeric', 'min:0'],
            'reward_points' => ['nullable', 'integer', 'min:0'],
            'reward_type' => ['nullable', 'string', 'in:points,airtime,prize_draw'],
            'prize_name' => ['nullable', 'string', 'max:255'],
            'prizes' => ['nullable', 'array'],
            'prizes.*.name' => ['required_with:prizes', 'string', 'max:255'],
            'prizes.*.amount' => ['nullable', 'numeric', 'min:0'],
            'prizes.*.points' => ['nullable', 'integer', 'min:0'],
            'response_cap' => ['required', 'integer', 'min:0'],
            'estimated_time' => ['nullable', 'integer', 'min:1'],
            'questions' => ['required', 'array', 'max:8'],
            'questions.*.id' => ['required', 'string'],
            'questions.*.text' => ['required', 'string'],
            'questions.*.type' => ['required', 'in:mcq,scale,checkbox,text,image_mcq'],
            'questions.*.options' => ['required_if:questions.*.type,mcq', 'required_if:questions.*.type,checkbox', 'required_if:questions.*.type,image_mcq', 'nullable', 'array'],
            'questions.*.required' => ['required', 'boolean'],
            'questions.*.logic' => ['nullable', 'array'],
            'enrichment_questions' => ['nullable', 'array', 'max:5'],
            'enrichment_questions.*.id' => ['required_with:enrichment_questions', 'string'],
            'enrichment_questions.*.text' => ['required_with:enrichment_questions', 'string'],
            'enrichment_questions.*.type' => ['required_with:enrichment_questions', 'in:mcq,scale,checkbox,text'],
            'enrichment_questions.*.options' => ['required_if:enrichment_questions.*.type,mcq', 'required_if:enrichment_questions.*.type,checkbox', 'nullable', 'array'],
            'target_gender' => ['nullable', 'string', 'max:50'],
            'target_age_band' => ['nullable', 'string', 'max:50'],
            'target_location' => ['nullable', 'string', 'max:100'],
            'target_employment' => ['nullable', 'string', 'max:50'],
            'target_income_band' => ['nullable', 'string', 'max:50'],
            'target_traits' => ['nullable', 'array'],
            'target_traits.*.key' => ['required_with:target_traits', 'string'],
            'target_traits.*.value' => ['required_with:target_traits', 'string'],
            'target_traits.*.operator' => ['nullable', 'in:eq,neq'],
            'exclude_traits' => ['nullable', 'array'],
            'exclude_traits.*.key' => ['required_with:exclude_traits', 'string'],
            'exclude_traits.*.value' => ['required_with:exclude_traits', 'string'],
            'exclude_traits.*.operator' => ['nullable', 'in:eq,neq'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data = $this->syncLegacyRewardFields($validated);

        if (! auth()->user()->isAdmin() && auth()->user()->client_id) {
            $data['client_id'] = auth()->user()->client_id;
        }

        Survey::create([
            'user_id' => auth()->id(),
            ...$data,
            'status' => ($data['is_active'] ?? false) ? 'active' : 'draft',
            'is_active' => $data['is_active'] ?? false,
        ]);

        return redirect()->back()->with('success', 'Survey created successfully.');
    }

    public function update(Request $request, Survey $survey)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'client_id' => ['nullable', 'exists:clients,id'],
            'reward_amount' => ['nullable', 'numeric', 'min:0'],
            'reward_points' => ['nullable', 'integer', 'min:0'],
            'reward_type' => ['nullable', 'string', 'in:points,airtime,prize_draw'],
            'prize_name' => ['nullable', 'string', 'max:255'],
            'prizes' => ['nullable', 'array'],
            'prizes.*.name' => ['required_with:prizes', 'string', 'max:255'],
            'prizes.*.amount' => ['nullable', 'numeric', 'min:0'],
            'prizes.*.points' => ['nullable', 'integer', 'min:0'],
            'response_cap' => ['required', 'integer', 'min:0'],
            'estimated_time' => ['nullable', 'integer', 'min:1'],
            'questions' => ['required', 'array', 'max:8'],
            'questions.*.id' => ['required', 'string'],
            'questions.*.text' => ['required', 'string'],
            'questions.*.type' => ['required', 'in:mcq,scale,checkbox,text,image_mcq'],
            'questions.*.options' => ['required_if:questions.*.type,mcq', 'required_if:questions.*.type,checkbox', 'required_if:questions.*.type,image_mcq', 'nullable', 'array'],
            'questions.*.required' => ['required', 'boolean'],
            'questions.*.logic' => ['nullable', 'array'],
            'enrichment_questions' => ['nullable', 'array', 'max:5'],
            'enrichment_questions.*.id' => ['required_with:enrichment_questions', 'string'],
            'enrichment_questions.*.text' => ['required_with:enrichment_questions', 'string'],
            'enrichment_questions.*.type' => ['required_with:enrichment_questions', 'in:mcq,scale,checkbox,text'],
            'enrichment_questions.*.options' => ['required_if:enrichment_questions.*.type,mcq', 'required_if:enrichment_questions.*.type,checkbox', 'nullable', 'array'],
            'target_gender' => ['nullable', 'string', 'max:50'],
            'target_age_band' => ['nullable', 'string', 'max:50'],
            'target_location' => ['nullable', 'string', 'max:100'],
            'target_employment' => ['nullable', 'string', 'max:50'],
            'target_income_band' => ['nullable', 'string', 'max:50'],
            'target_traits' => ['nullable', 'array'],
            'target_traits.*.key' => ['required_with:target_traits', 'string'],
            'target_traits.*.value' => ['required_with:target_traits', 'string'],
            'target_traits.*.operator' => ['nullable', 'in:eq,neq'],
            'exclude_traits' => ['nullable', 'array'],
            'exclude_traits.*.key' => ['required_with:exclude_traits', 'string'],
            'exclude_traits.*.value' => ['required_with:exclude_traits', 'string'],
            'exclude_traits.*.operator' => ['nullable', 'in:eq,neq'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data = $this->syncLegacyRewardFields($validated);

        // A survey in draw phase cannot be reactivated until the draw is closed
        if ($survey->draw_phase_active) {
            $data['is_active'] = false;
            $data['status'] = 'closed';
        } elseif (isset($data['is_active'])) {
            $data['status'] = $data['is_active'] ? 'active' : 'draft';
        }

        $survey->update($data);

        $message = 'Survey updated successfully.';
        if ($survey->draw_phase_active) {
            $message .= ' (Draw phase is active — survey cannot be re-opened until the draw is closed.)';
        }

        return redirect()->back()->with('success', $message);
    }

    public function destroy(Survey $survey)
    {
        $survey->delete();

        return redirect()->back()->with('success', 'Survey deleted successfully.');
    }

    public function clone(Survey $survey)
    {
        $newSurvey = $survey->replicate();
        $newSurvey->title .= ' (Copy)';
        $newSurvey->response_count = 0;
        $newSurvey->status = 'draft';
        $newSurvey->is_active = false;
        $newSurvey->user_id = auth()->id();
        $newSurvey->save();

        return redirect()->back()->with('success', 'Survey cloned successfully.');
    }

    public function previewReach(Request $request)
    {
        $data = $request->validate([
            'target_gender' => ['nullable', 'string'],
            'target_age_band' => ['nullable', 'string'],
            'target_location' => ['nullable', 'string'],
            'target_employment' => ['nullable', 'string'],
            'target_income_band' => ['nullable', 'string'],
        ]);

        $count = User::when($data['target_gender'] ?? null, function ($q, $value) {
            $q->whereHas('profile', fn ($p) => $p->where('gender', $value));
        })
            ->when($data['target_age_band'] ?? null, function ($q, $value) {
                $q->whereHas('profile', fn ($p) => $p->where('age_band', $value));
            })
            ->when($data['target_location'] ?? null, function ($q, $value) {
                $q->whereHas('profile', fn ($p) => $p->where('location', $value));
            })
            ->when($data['target_employment'] ?? null, function ($q, $value) {
                $q->whereHas('profile', fn ($p) => $p->where('employment', $value));
            })
            ->when($data['target_income_band'] ?? null, function ($q, $value) {
                $q->whereHas('profile', fn ($p) => $p->where('income_band', $value));
            })
            ->count();

        return response()->json(['eligible_users' => $count]);
    }

    public function toggleStatus(Survey $survey)
    {
        $survey->is_active = ! $survey->is_active;
        $survey->status = $survey->is_active ? 'active' : 'draft';
        $survey->save();

        if ($survey->is_active) {
            // Notify eligible users who have push subscriptions and have not been notified yet
            User::whereHas('pushSubscriptions')
                ->whereDoesntHave('surveyNotificationLogs', fn ($q) => $q->where('survey_id', $survey->id))
                ->when($survey->target_gender, function ($q, $gender) {
                    $q->whereHas('profile', fn ($p) => $p->where('gender', $gender));
                })
                ->when($survey->target_age_band, function ($q, $age) {
                    $q->whereHas('profile', fn ($p) => $p->where('age_band', $age));
                })
                ->when($survey->target_location, function ($q, $location) {
                    $q->whereHas('profile', fn ($p) => $p->where('location', $location));
                })
                ->when($survey->target_employment, function ($q, $employment) {
                    $q->whereHas('profile', fn ($p) => $p->where('employment', $employment));
                })
                ->when($survey->target_income_band, function ($q, $income) {
                    $q->whereHas('profile', fn ($p) => $p->where('income_band', $income));
                })
                ->chunk(200, function ($users) use ($survey) {
                    Notification::send($users, new NewSurveyNotification($survey->title, $survey->id, $survey->description));

                    $logs = $users->map(fn ($user) => [
                        'survey_id' => $survey->id,
                        'user_id'   => $user->id,
                        'sent_at'   => now(),
                    ])->all();

                    SurveyNotificationLog::upsert($logs, ['survey_id', 'user_id'], ['sent_at']);
                });
        }

        return redirect()->back()->with('success', 'Survey status updated.');
    }

    public function exportQuestionToBank(Request $request, Survey $survey)
    {
        $data = $request->validate([
            'question_index' => ['nullable', 'integer'],
            // Ensure the template is stored against a specific client
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $questions = $survey->questions ?? [];

        if (isset($data['question_index'])) {
            $question = $questions[$data['question_index']] ?? null;

            if (! $question) {
                return response()->json(['error' => 'Question not found.'], 404);
            }

            $questionsToSave = [$question];
            $defaultName = "{$survey->title} · Q" . ($data['question_index'] + 1);
        } else {
            if (empty($questions)) {
                return response()->json(['error' => 'No questions found on this survey.'], 404);
            }

            $questionsToSave = $questions;
            $defaultName = "{$survey->title} · Template";
        }

        QuestionTemplate::create([
            'user_id' => auth()->id(),
            'client_id' => $data['client_id'],
            'name' => $data['name'] ?? $defaultName,
            'questions' => $questionsToSave,
            'category' => $data['category'] ?? 'Exported',
        ]);

        return redirect()->back()->with('success', 'Saved to question library.');
    }

    public function startDrawPhase(Survey $survey)
    {
        if (! $survey->prize_draw_enabled && $survey->reward_type !== 'airtime') {
            return redirect()->back()->with('error', 'This survey does not support a draw phase.');
        }

        if ($survey->draw_phase_active) {
            return redirect()->back()->with('error', 'Draw phase is already active.');
        }

        $survey->update([
            'draw_phase_active' => true,
            'status' => 'closed',
            'is_active' => false,
        ]);

        $users = User::whereHas('responses', function ($q) use ($survey) {
            $q->where('survey_id', $survey->id);
        })->get();

        Notification::send($users, new PrizeDrawInvitation($survey));

        return redirect()->back()->with('success', 'Draw phase started and invitations sent to respondents.');
    }

    private function syncLegacyRewardFields(array $data): array
    {
        $prizes = $data['prizes'] ?? [];

        if (! empty($prizes)) {
            $first = $prizes[0];
            $data['prize_name']     = $first['name'] ?? null;
            $data['reward_amount']  = $first['amount'] ?? 0;
            $data['reward_points']  = $first['points'] ?? 0;
        }

        $data['reward_amount'] = $data['reward_amount'] ?? 0;
        $data['reward_points'] = $data['reward_points'] ?? 0;

        return $data;
    }
}
