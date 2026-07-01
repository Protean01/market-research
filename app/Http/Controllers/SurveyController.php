<?php

namespace App\Http\Controllers;

use AfricasTalking\SDK\AfricasTalking;
use App\Models\PrizeDrawEntry;
use App\Models\Response;
use App\Models\Setting;
use App\Models\Survey;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SurveyController extends Controller
{
    public function index(Request $request)
    {
        $surveys = $this->filterByTraits(
            $this->availableQuery($request->user())->get(),
            $request->user()
        );

        \Log::info('SurveyController@index for user '.$request->user()?->id.': '.$surveys->count().' surveys found');

        return Inertia::render('SurveyList', [
            'surveys' => $surveys,
        ]);
    }

    public function available(Request $request)
    {
        $surveys = $this->filterByTraits(
            $this->availableQuery($request->user())->get(),
            $request->user()
        );

        \Log::info('SurveyController@available for user '.$request->user()?->id.': '.$surveys->count().' surveys found');

        return response()->json($surveys);
    }

    public function show(Survey $survey)
    {
        $user = request()->user();
        $profile = $user?->profile;

        // Normalize legacy question types so the frontend renders them
        $survey->questions = collect($survey->questions ?? [])->map(function ($q) {
            $type = $q['type'] ?? 'mcq';
            $normalized = match ($type) {
                'multiple_choice', 'single_choice' => 'mcq',
                'rating', 'likert', 'scale' => 'scale',
                default => $type,
            };

            return [
                ...$q,
                'type' => $normalized,
            ];
        })->values()->all();

        // Suppress enrichment questions already answered; limit to 2 new ones
        $enrichment = collect($survey->enrichment_questions ?? [])
            ->filter(function ($question) use ($profile) {
                $id = $question['id'] ?? null;

                return $id && ! ($profile?->enriched_attributes[$id] ?? false);
            })
            ->take(2)
            ->values()
            ->all();

        $survey->enrichment_questions = $enrichment;

        return Inertia::render('SurveyActive', [
            'survey' => $survey,
        ]);
    }

    public function submit(Request $request, Survey $survey)
    {
        \Log::info('Survey submit entry', ['survey_id' => $survey->id, 'user_id' => $request->user()?->id]);
        $user = $request->user();

        if (! $this->isEligibleForSubmission($survey, $user)) {
            return response()->json(['error' => 'You are not eligible for this survey'], 403);
        }

        // Simple velocity check to prevent rapid repeat submissions
        $lockKey = "survey_submit_lock_{$user->id}_{$survey->id}";
        if (cache()->has($lockKey)) {
            return response()->json(['error' => 'Please wait a few seconds before submitting again'], 429);
        }

        $data = $request->validate([
            'answers' => ['required', 'array'],
            'enrichment_answers' => ['nullable', 'array', 'max:10'],
            'time_taken' => ['nullable', 'integer'],
        ]);

        // Fraud Check: Velocity / Speed Trap
        $isFlagged = false;
        $flagReason = null;
        $qualityScore = 100;

        if (isset($data['time_taken'])) {
            $estimatedSeconds = ($survey->estimated_time ?? 5) * 60;
            $minThreshold = $estimatedSeconds * 0.2; // 20% of estimated time

            if ($data['time_taken'] < $minThreshold) {
                $isFlagged = true;
                $flagReason = 'Speed Trap: Completed too quickly';
                $qualityScore = 30;
            }
        }

        \Log::info('Survey submission attempt', [
            'survey_id' => $survey->id,
            'user_id' => $user->id,
            'answers_provided' => array_keys($data['answers'] ?? []),
        ]);

        // Enforce required questions answered (completion-only rewards)
        $requiredIds = collect($survey->questions ?? [])
            ->filter(function ($q) {
                $isSkipLogic = ! empty($q['logic']);
                $isVisibilityLogic = ! empty($q['visibility']);

                return ($q['required'] ?? false) && ! $isSkipLogic && ! $isVisibilityLogic;
            })
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();

        foreach ($requiredIds as $qid) {
            if (! array_key_exists($qid, $data['answers'])) {
                \Log::warning('Missing required answer', ['question_id' => $qid]);

                return response()->json(['error' => 'Please answer all required questions'], 422);
            }
        }

        try {
            return \DB::transaction(function () use ($request, $survey, $user, $data, $isFlagged, $flagReason, $qualityScore) {
                // Lock the user, survey, and wallet to prevent race conditions
                $userLocked = \App\Models\User::where('id', $user->id)->lockForUpdate()->first();
                $survey = Survey::where('id', $survey->id)->lockForUpdate()->first();
                $wallet = $userLocked->wallet()->lockForUpdate()->first();
                if (! $wallet) {
                    $wallet = $userLocked->wallet()->create(['balance' => 0, 'points' => 0]);
                    $wallet = $userLocked->wallet()->lockForUpdate()->first();
                }

                if ($survey->response_cap && $survey->responses()->count() >= $survey->response_cap) {
                    throw new \Exception('Response cap reached', 409);
                }

                // Consistently calculate points: prefer reward_points, fallback to reward_amount * 10
                $earnedPoints = $survey->reward_points > 0
                    ? $survey->reward_points
                    : (int) round(($survey->reward_amount ?? 0) * 10);

                $dailyCap = (int) Setting::get('reward_daily_cap', env('REWARD_DAILY_CAP', 0));
                $weeklyCap = (int) Setting::get('reward_weekly_cap', env('REWARD_WEEKLY_CAP', 0));

                if ($dailyCap > 0 || $weeklyCap > 0) {
                    $dailyEarned = $wallet->transactions()
                        ->where('type', 'earn')
                        ->where('created_at', '>=', now()->subDay())
                        ->sum('points');
                    $weeklyEarned = $wallet->transactions()
                        ->where('type', 'earn')
                        ->where('created_at', '>=', now()->subDays(7))
                        ->sum('points');

                    if ($dailyCap > 0 && ($dailyEarned + $earnedPoints) > $dailyCap) {
                        throw new \Exception('Daily earning cap reached', 403);
                    }

                    if ($weeklyCap > 0 && ($weeklyEarned + $earnedPoints) > $weeklyCap) {
                        throw new \Exception('Weekly earning cap reached', 403);
                    }
                }

                // Lock existing response row to prevent concurrent double-awards
                $existingResponse = Response::where('survey_id', $survey->id)
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                $pointsAlreadyAwarded = $existingResponse && $existingResponse->earned_points > 0;

                $response = Response::updateOrCreate(
                    ['survey_id' => $survey->id, 'user_id' => $user->id],
                    [
                        'answers' => $data['answers'],
                        'enrichment_answers' => $data['enrichment_answers'] ?? [],
                        'earned_points' => $earnedPoints,
                        'time_taken' => $data['time_taken'] ?? null,
                        'is_flagged' => $isFlagged,
                        'flag_reason' => $flagReason,
                        'quality_score' => $qualityScore,
                        'completed_at' => now(),
                    ],
                );

                if (! $pointsAlreadyAwarded) {
                    $survey->increment('response_count');

                    if ($isFlagged) {
                        \Log::warning("Flagged response - points withheld for user {$user->id} on survey {$survey->id}: {$flagReason}");
                    }

                    // Apply Streak Bonus (10% extra if streak >= 5)
                    $profile = $user->profile;
                    $finalPoints = $earnedPoints;
                    $bonusApplied = false;

                    if ($profile && $profile->current_streak >= 5) {
                        $finalPoints = (int) round($earnedPoints * 1.1);
                        $bonusApplied = true;
                    }

                    // Apply Tier Multiplier (stacks on top of streak bonus)
                    $tierMultiplier = $user->tierMultiplier();
                    if ($tierMultiplier > 1.0) {
                        $finalPoints = (int) round($finalPoints * $tierMultiplier);
                    }

                    // Automatic Reward Processing based on Survey Configuration
                    $rewardType = $survey->reward_type ?? 'points';

                    if (! $isFlagged) {
                        // Always award participation points for every survey
                        if ($finalPoints > 0) {
                            $wallet->increment('points', $finalPoints);
                            $wallet->transactions()->create([
                                'type' => 'earn',
                                'points' => $finalPoints,
                                'status' => 'completed',
                                'meta' => [
                                    'survey_id' => $survey->id,
                                    'survey_title' => $survey->title,
                                    'streak_bonus' => $bonusApplied,
                                    'tier' => $user->tier(),
                                    'tier_multiplier' => $tierMultiplier,
                                ],
                            ]);
                        }

                        // For prize draw / airtime surveys: also create a draw entry
                        if ($rewardType === 'airtime' || $rewardType === 'prize_draw') {
                            PrizeDrawEntry::create([
                                'survey_id' => $survey->id,
                                'user_id' => $user->id,
                                'points_entered' => 0,
                            ]);
                        }

                        \Log::info("Reward processed for user {$user->id} for survey {$survey->id}: Type {$rewardType}, Points {$finalPoints}");
                    }
                } else {
                    \Log::info("Points skipped for user {$user->id} (already awarded) for survey {$survey->id}");
                }

                if (! empty($data['enrichment_answers']) || ! $pointsAlreadyAwarded) {
                    $profile = $user->profile ?? $user->profile()->create();

                    if (! $pointsAlreadyAwarded) {
                        // Update Streak Logic (Calendar Day comparison)
                        $lastSurvey = $profile->last_survey_at;
                        $now = now();

                        if ($lastSurvey) {
                            $diffInDays = $lastSurvey->startOfDay()->diffInDays($now->startOfDay());
                            if ($diffInDays === 1) {
                                $profile->current_streak += 1;
                            } elseif ($diffInDays > 1) {
                                $profile->current_streak = 1;
                            }
                        } else {
                            $profile->current_streak = 1;
                        }

                        if ($profile->current_streak > $profile->longest_streak) {
                            $profile->longest_streak = $profile->current_streak;
                        }

                        $profile->last_survey_at = $now;

                        // Update Trust Score (Average of last 5 responses)
                        $recentResponses = Response::where('user_id', $user->id)
                            ->whereNotNull('quality_score')
                            ->orderByDesc('completed_at')
                            ->take(5)
                            ->get();

                        if ($recentResponses->isEmpty()) {
                            $profile->trust_score = 100;
                        } else {
                            $profile->trust_score = (int) round($recentResponses->avg('quality_score'));
                        }
                    }

                    $attributes = $profile->enriched_attributes ?? [];
                    foreach ($data['enrichment_answers'] as $answer) {
                        if (isset($answer['questionId'])) {
                            $attributes[$answer['questionId']] = $answer['answer'] ?? null;
                        }
                    }
                    $profile->enriched_attributes = $attributes;
                    $profile->save();
                }

                $responseData = ['message' => 'Survey submitted', 'points' => $wallet->fresh()->points];

                // Immediately redirect to slot machine for draw/airtime surveys with active draw phase
                if (! $isFlagged && \in_array($rewardType, ['prize_draw', 'airtime'], true)) {
                    if ($survey->draw_phase_active) {
                        $responseData['slot_machine_url'] = route('survey.slot-machine', $survey->id, false);
                    }
                }

                cache()->put("survey_submit_lock_{$user->id}_{$survey->id}", true, now()->addSeconds(30));

                return $request->expectsJson()
                    ? response()->json($responseData)
                    : back()->with('status', 'Survey submitted');
            });
        } catch (\Throwable $e) {
            \Log::error('Survey submission failed (Throwable)', [
                'user_id' => $user->id,
                'survey_id' => $survey->id,
                'message' => $e->getMessage(),
                'status' => $e instanceof \Exception ? $e->getCode() : 400,
            ]);

            return response()->json(['error' => $e->getMessage()], $e instanceof \Exception && $e->getCode() >= 400 ? $e->getCode() : 400);
        }
    }

    private function isEligibleForSubmission(Survey $survey, $user): bool
    {
        // Allow-all override for local testing
        if (app()->environment('local')) {
            return true;
        }

        // Allow admins locally for testing when flag enabled
        $allowAdminLocal = config('app.allow_admin_submissions', false);

        if (! $user) {
            return false;
        }

        // Short-circuit for local admin/staff testing
        if ($user->isAdmin() && $allowAdminLocal) {
            return true;
        }

        if ($user->isAdmin() && ! $allowAdminLocal) {
            return false;
        }

        if (! $survey->is_active) {
            return false;
        }

        if ($survey->response_cap && $survey->response_count >= $survey->response_cap) {
            return false;
        }

        $profile = $user->profile;

        $matches = static function ($target, $value): bool {
            if ($target === null || $target === '') {
                return true;
            }
            if ($value === null) {
                return false;
            }

            // Fix for "Female" containing "Male"
            $targets = is_string($target) ? array_map('trim', explode(',', $target)) : [$target];

            return in_array($value, $targets);
        };

        if (! $matches($survey->target_gender, $profile?->gender)) {
            return false;
        }

        if (! $matches($survey->target_age_band, $profile?->age_band)) {
            return false;
        }

        if (! $matches($survey->target_location, $profile?->location)) {
            return false;
        }

        if (! $matches($survey->target_employment, $profile?->employment)) {
            return false;
        }

        if (! $matches($survey->target_income_band, $profile?->income_band)) {
            return false;
        }

        // Trait-based targeting
        $attributes = $profile?->enriched_attributes ?? [];

        foreach ($survey->target_traits ?? [] as $rule) {
            $key = $rule['key'] ?? null;
            $value = $rule['value'] ?? null;
            $op = $rule['operator'] ?? 'eq';

            if (! $key) {
                continue;
            }

            $userVal = $attributes[$key] ?? null;

            if ($op === 'eq' && $userVal !== $value) {
                return false;
            }

            if ($op === 'neq' && $userVal === $value) {
                return false;
            }
        }

        foreach ($survey->exclude_traits ?? [] as $rule) {
            $key = $rule['key'] ?? null;
            $value = $rule['value'] ?? null;
            $op = $rule['operator'] ?? 'eq';

            if (! $key) {
                continue;
            }

            $userVal = $attributes[$key] ?? null;

            if ($op === 'eq' && $userVal === $value) {
                return false;
            }

            if ($op === 'neq' && $userVal !== null && $userVal !== $value) {
                return false;
            }
        }

        return true;
    }

    private function availableQuery($user)
    {
        // 1. Strictly exclude Admins/Staff from taking surveys
        if ($user?->isAdmin()) {
            return Survey::query()->whereRaw('1 = 0'); // Return empty query
        }

        $profile = $user?->profile;
        $completedIds = Response::where('user_id', $user?->id)->pluck('survey_id');

        $applyTargeting = function ($query, $column, $value) {
            if ($value === null || $value === '') {
                $query->whereNull($column);
            } else {
                $query->where(function ($q) use ($column, $value) {
                    $q->whereNull($column)
                        ->orWhere($column, $value)
                        ->orWhere($column, 'like', "{$value},%")
                        ->orWhere($column, 'like', "%,{$value}")
                        ->orWhere($column, 'like', "%,{$value},%");
                });
            }
        };

        return Survey::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('response_cap')
                    ->orWhere('response_cap', 0)
                    ->orWhereColumn('response_count', '<', 'response_cap');
            })
            ->where(function ($q) use ($profile, $applyTargeting) {
                $applyTargeting($q, 'target_gender', $profile?->gender);
            })
            ->where(function ($q) use ($profile, $applyTargeting) {
                $applyTargeting($q, 'target_age_band', $profile?->age_band);
            })
            ->where(function ($q) use ($profile, $applyTargeting) {
                $applyTargeting($q, 'target_location', $profile?->location);
            })
            ->where(function ($q) use ($profile, $applyTargeting) {
                $applyTargeting($q, 'target_employment', $profile?->employment);
            })
            ->where(function ($q) use ($profile, $applyTargeting) {
                $applyTargeting($q, 'target_income_band', $profile?->income_band);
            })
            ->where(function ($q) use ($profile, $applyTargeting) {
                $applyTargeting($q, 'target_language', $profile?->language);
            })
            ->whereNotIn('id', $completedIds);
    }

    private function filterByTraits($surveys, $user)
    {
        $attributes = $user?->profile?->enriched_attributes ?? [];

        return $surveys->filter(function (Survey $survey) use ($attributes) {
            $includes = $survey->target_traits ?? [];
            foreach ($includes as $rule) {
                $key = $rule['key'] ?? null;
                $value = $rule['value'] ?? null;
                $op = $rule['operator'] ?? 'eq';

                if (! $key) {
                    continue;
                }

                $userVal = $attributes[$key] ?? null;
                if ($op === 'eq' && $userVal !== $value) {
                    return false;
                }
                if ($op === 'neq' && $userVal === $value) {
                    return false;
                }
            }

            $excludes = $survey->exclude_traits ?? [];
            foreach ($excludes as $rule) {
                $key = $rule['key'] ?? null;
                $value = $rule['value'] ?? null;
                $op = $rule['operator'] ?? 'eq';

                if (! $key) {
                    continue;
                }

                $userVal = $attributes[$key] ?? null;
                if ($op === 'eq' && $userVal === $value) {
                    return false;
                }
                if ($op === 'neq' && $userVal !== null && $userVal !== $value) {
                    return false;
                }
            }

            return true;
        })->values();
    }
}
