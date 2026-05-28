<?php

namespace App\Http\Controllers;

use AfricasTalking\SDK\AfricasTalking;
use App\Models\PrizeDrawEntry;
use App\Models\Survey;
use App\Notifications\PrizeDrawResultNotification;
use App\Notifications\PrizeDrawWinnerNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PrizeDrawController extends Controller
{
    public function enter(Request $request, Survey $survey)
    {
        $user = $request->user();
        Log::info('Prize draw entry attempt', ['survey_id' => $survey->id, 'user_id' => $user->id]);

        if ($survey->reward_type !== 'prize_draw') {
            return response()->json(['error' => 'Prize draw is not enabled for this survey.'], 403);
        }

        $wallet = $user->wallet ?? $user->wallet()->create();

        $entryCost = (int) config('app.prize_draw_entry_cost', 50);

        if ($entryCost <= 0) {
            return response()->json(['error' => 'No points to enter into the draw.'], 422);
        }

        if ($wallet->points < $entryCost) {
            return response()->json(['error' => 'Insufficient points.'], 422);
        }

        $completed = $user->responses()->where('survey_id', $survey->id)->exists();
        if (! $completed) {
            return response()->json(['error' => 'You must complete this survey before entering the draw.'], 403);
        }

        $alreadyEntered = PrizeDrawEntry::where('survey_id', $survey->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($alreadyEntered) {
            return response()->json(['error' => 'You have already entered this prize draw.'], 409);
        }

        DB::transaction(function () use ($wallet, $survey, $user, $entryCost) {
            $wallet->decrement('points', $entryCost);
            $wallet->transactions()->create([
                'type' => 'redeem',
                'points' => $entryCost,
                'meta' => [
                    'type' => 'prize_draw',
                    'survey_id' => $survey->id,
                    'survey_title' => $survey->title,
                ],
            ]);
            PrizeDrawEntry::create([
                'survey_id' => $survey->id,
                'user_id' => $user->id,
                'points_entered' => $entryCost,
            ]);
        });

        if ($request->header('X-Inertia')) {
            return back()->with('status', 'You have been entered into the prize draw!');
        }

        return $request->expectsJson()
            ? response()->json([
                'message' => 'You have been entered into the prize draw!',
                'points' => $wallet->fresh()->points,
            ])
            : back()->with('status', 'You have been entered into the prize draw!');
    }

    /**
     * Admin-triggered draw. Automatically selects one unique winner per prize
     * defined in survey->prizes[]. No need to click multiple times.
     * Falls back to single-winner behaviour for surveys without a prizes array.
     */
    public function draw(Survey $survey)
    {
        if (! auth()->user()->isAdmin() && $survey->user_id !== auth()->id()) {
            abort(403);
        }

        if ($survey->reward_type === 'airtime') {
            return back()->with('error', 'Airtime surveys use the slot machine — use Start Airtime Claim Phase instead.');
        }

        $prizes = $survey->prizes ?? [];
        $drawnWinners = [];

        DB::transaction(function () use ($survey, $prizes, &$drawnWinners) {
            // Track user IDs assigned a prize this run to avoid duplicates within the transaction
            $assignedUserIds = PrizeDrawEntry::where('survey_id', $survey->id)
                ->where('is_winner', true)
                ->pluck('user_id')
                ->toArray();

            if (empty($prizes)) {
                // ── Legacy single-prize behaviour ──────────────────────────────
                $winner = PrizeDrawEntry::with('user')
                    ->where('survey_id', $survey->id)
                    ->where('is_winner', false)
                    ->whereNotIn('user_id', $assignedUserIds)
                    ->lockForUpdate()
                    ->inRandomOrder()
                    ->first();

                if (! $winner) {
                    return;
                }

                /** @var PrizeDrawEntry $winner */
                $prizePoints = $this->resolvePrizePoints($survey);
                $this->awardPoints($winner->user, $survey, $prizePoints, 'prize_draw_win');
                $winner->update(['is_winner' => true, 'won_at' => now()]);

                $drawnWinners[] = ['entry' => $winner, 'points' => $prizePoints, 'prize' => null];
            } else {
                // ── Multi-prize: one winner per unawarded prize ────────────────
                foreach ($prizes as $index => $prize) {
                    $alreadyClaimed = PrizeDrawEntry::where('survey_id', $survey->id)
                        ->where('prize_index', $index)
                        ->where('is_winner', true)
                        ->exists();

                    if ($alreadyClaimed) {
                        continue;
                    }

                    $winner = PrizeDrawEntry::with('user')
                        ->where('survey_id', $survey->id)
                        ->where('is_winner', false)
                        ->whereNotIn('user_id', $assignedUserIds)
                        ->lockForUpdate()
                        ->inRandomOrder()
                        ->first();

                    if (! $winner) {
                        break; // Not enough entries for remaining prizes
                    }

                    /** @var PrizeDrawEntry $winner */
                    $prizePoints = $this->prizeMoney($prize, $survey);
                    $this->awardPoints($winner->user, $survey, $prizePoints, 'prize_draw_win');
                    $winner->update([
                        'is_winner'      => true,
                        'won_at'         => now(),
                        'prize_index'    => $index,
                        'prize_snapshot' => $prize,
                    ]);

                    $assignedUserIds[] = $winner->user_id;
                    $drawnWinners[] = ['entry' => $winner, 'points' => $prizePoints, 'prize' => $prize];
                }
            }
        });

        if (empty($drawnWinners)) {
            return back()->with('error', 'No eligible entries found for this draw.');
        }

        foreach ($drawnWinners as $w) {
            $w['entry']->user->notify(new PrizeDrawWinnerNotification($survey, $w['points']));
            Log::info("WINNER DRAWN for survey {$survey->id}: User {$w['entry']->user_id}, prize {$w['points']} pts", [
                'prize' => $w['prize'],
            ]);
        }

        $count = \count($drawnWinners);
        $names = implode(', ', array_map(fn ($w) => $w['entry']->user->name, $drawnWinners));

        return back()->with('success', "{$count} winner(s) drawn: {$names}");
    }

    public function slotMachine(Survey $survey)
    {
        if (! $survey->draw_phase_active && ! app()->environment('local')) {
            return redirect()->route('dashboard')->with('error', 'Prize draw is not active for this survey.');
        }

        $isAirtime = $survey->reward_type === 'airtime';
        $prizes     = $survey->prizes ?? [];

        $entrantCount    = PrizeDrawEntry::where('survey_id', $survey->id)->count();
        $userEntry       = PrizeDrawEntry::where('survey_id', $survey->id)->where('user_id', auth()->id())->first();
        $winnersCount    = PrizeDrawEntry::where('survey_id', $survey->id)->where('is_winner', true)->count();
        $prizesRemaining = \count($prizes) > 0
            ? max(0, \count($prizes) - $winnersCount)
            : ($winnersCount > 0 ? 0 : 1);

        // Build a display label for the prize
        if ($isAirtime && \count($prizes) > 0) {
            $currency  = config('services.africastalking.currency', 'ZMW');
            $prizeName = implode(' / ', array_map(fn ($p) => "{$currency} {$p['amount']} Airtime", $prizes));
        } elseif ($isAirtime) {
            $currency  = config('services.africastalking.currency', 'ZMW');
            $prizeName = "{$currency} {$survey->reward_amount} Airtime";
        } else {
            $prizeName = $survey->prize_name ?? 'Survey Prize';
        }

        return Inertia::render('SlotMachine', [
            'survey'          => $survey,
            'prizeName'       => $prizeName,
            'prizes'          => $prizes,
            'prizesRemaining' => $prizesRemaining,
            'winnersCount'    => $winnersCount,
            'rewardType'      => $survey->reward_type,
            'rewardAmount'    => $survey->reward_amount,
            'hasSpun'         => $userEntry?->has_spun ?? false,
            'entrantCount'    => $entrantCount,
        ]);
    }

    public function spin(Survey $survey)
    {
        $user = auth()->user();

        if (! $survey->draw_phase_active) {
            return response()->json(['error' => 'Prize draw is not active for this survey.'], 403);
        }

        $entry = PrizeDrawEntry::where('survey_id', $survey->id)->where('user_id', $user->id)->first();

        if (! $entry) {
            return response()->json(['error' => 'You are not entered into this prize draw.'], 403);
        }

        if ($entry->has_spun) {
            return response()->json(['error' => 'You have already spun the slot machine!'], 409);
        }

        $isAirtime  = $survey->reward_type === 'airtime';
        $prizes     = $survey->prizes ?? [];
        $multiPrize = \count($prizes) > 0;

        // ── Serialize concurrent spins per survey with a distributed lock ──────
        // Prevents two simultaneous requests from both computing "you win" for
        // the same prize slot before either write has committed.
        $lock = Cache::lock("prize_draw_spin_{$survey->id}", 15);
        if (! $lock->get()) {
            return response()->json(['error' => 'Please wait a moment and try again.'], 429);
        }

        $isWinner      = false;
        $assignedPrize = null;
        $prizeIndex    = null;
        $prizePoints       = 0;
        $consolationPoints = 0;
        $airtimeAmount     = null;
        $drawJustClosed    = false;
        $prizeName         = null;
        $unspunForConsolation = collect();

        try {
            DB::transaction(function () use (
                $survey, $user, $isAirtime, $multiPrize, $prizes,
                $entry, &$isWinner, &$assignedPrize, &$prizeIndex,
                &$prizePoints, &$consolationPoints, &$drawJustClosed,
                &$unspunForConsolation
            ) {
                // Re-check has_spun under lock to guard against double-submission
                $lockedEntry = PrizeDrawEntry::where('id', $entry->id)->lockForUpdate()->first();
                if ($lockedEntry->has_spun) {
                    throw new \RuntimeException('already_spun');
                }

                // ── Win determination (inside transaction + lock) ──────────────
                if ($isAirtime && ! $multiPrize) {
                    $isWinner = true;
                } elseif ($multiPrize) {
                    $totalEntrants     = PrizeDrawEntry::where('survey_id', $survey->id)->count();
                    $alreadySpunCount  = PrizeDrawEntry::where('survey_id', $survey->id)->where('has_spun', true)->count();
                    $winnersCount      = PrizeDrawEntry::where('survey_id', $survey->id)->where('is_winner', true)->lockForUpdate()->count();
                    $remainingPrizes   = \count($prizes) - $winnersCount;
                    $remainingSpinners = max(1, $totalEntrants - $alreadySpunCount);

                    if ($remainingPrizes > 0) {
                        $isWinner = $remainingSpinners <= $remainingPrizes
                            ? true
                            : (rand(1, $remainingSpinners) <= $remainingPrizes);
                    }

                    if ($isWinner) {
                        $wonIndices = PrizeDrawEntry::where('survey_id', $survey->id)
                            ->where('is_winner', true)
                            ->pluck('prize_index')
                            ->toArray();

                        foreach ($prizes as $i => $prize) {
                            if (! \in_array($i, $wonIndices, true)) {
                                $prizeIndex    = $i;
                                $assignedPrize = $prize;
                                break;
                            }
                        }

                        if ($assignedPrize === null) {
                            $isWinner = false; // Prize taken by a concurrent spin
                        }
                    }
                } else {
                    // Legacy single prize_draw — use entry count, not response count
                    $winnerExists = PrizeDrawEntry::where('survey_id', $survey->id)
                        ->where('is_winner', true)
                        ->lockForUpdate()
                        ->exists();

                    if (! $winnerExists) {
                        $totalEntrants    = PrizeDrawEntry::where('survey_id', $survey->id)->count();
                        $alreadySpunCount = PrizeDrawEntry::where('survey_id', $survey->id)->where('has_spun', true)->count();
                        $remaining        = max(1, $totalEntrants - $alreadySpunCount);
                        $isWinner         = $remaining <= 1 ? true : (rand(1, $remaining) === 1);
                    }
                }

                // ── Resolve prize value ────────────────────────────────────────
                if ($isWinner) {
                    if ($isAirtime) {
                        $airtimeAmount = $assignedPrize ? (float) $assignedPrize['amount'] : (float) $survey->reward_amount;
                        $prizeName     = $assignedPrize['name'] ?? null;
                    } else {
                        $prizePoints = $assignedPrize
                            ? $this->prizeMoney($assignedPrize, $survey)
                            : $this->resolvePrizePoints($survey);
                        $prizeName   = $assignedPrize['name'] ?? null;
                    }
                }

                // ── Persist entry outcome ──────────────────────────────────────
                $lockedEntry->update([
                    'has_spun'       => true,
                    'is_winner'      => $isWinner,
                    'won_at'         => $isWinner ? now() : null,
                    'prize_index'    => $isWinner ? $prizeIndex : null,
                    'prize_snapshot' => $isWinner ? $assignedPrize : null,
                ]);

                $wallet = $user->wallet ?? $user->wallet()->create();

                if ($isWinner && ! $isAirtime) {
                    $wallet->increment('points', $prizePoints);
                    $wallet->transactions()->create([
                        'type'   => 'earn',
                        'points' => $prizePoints,
                        'status' => 'completed',
                        'meta'   => ['type' => 'prize_draw_win', 'survey_id' => $survey->id, 'prize_index' => $prizeIndex],
                    ]);
                }

                if (! $isWinner && ! $isAirtime) {
                    $base              = $this->resolvePrizePoints($survey);
                    $consolationPoints = max(1, (int) round($base * 0.20));
                    $wallet->increment('points', $consolationPoints);
                    $wallet->transactions()->create([
                        'type'   => 'earn',
                        'points' => $consolationPoints,
                        'status' => 'completed',
                        'meta'   => ['type' => 'prize_draw_consolation', 'survey_id' => $survey->id],
                    ]);
                }

                // ── Draw closure check ─────────────────────────────────────────
                if ($multiPrize) {
                    $totalEntries = PrizeDrawEntry::where('survey_id', $survey->id)->count();
                    $spunCount    = PrizeDrawEntry::where('survey_id', $survey->id)->where('has_spun', true)->count();
                    $winnersNow   = PrizeDrawEntry::where('survey_id', $survey->id)->where('is_winner', true)->count();

                    if ($winnersNow >= \count($prizes) || $spunCount >= $totalEntries) {
                        $survey->update(['draw_phase_active' => false]);
                        $drawJustClosed = true;
                    }
                } elseif ($isAirtime) {
                    $totalEntries = PrizeDrawEntry::where('survey_id', $survey->id)->count();
                    $spunCount    = PrizeDrawEntry::where('survey_id', $survey->id)->where('has_spun', true)->count();
                    if ($spunCount >= $totalEntries) {
                        $survey->update(['draw_phase_active' => false]);
                        $drawJustClosed = true;
                    }
                } else {
                    if ($isWinner) {
                        $survey->update(['draw_phase_active' => false]);
                        $drawJustClosed = true;
                    }
                }

                // ── Consolation for unspun entrants (DB writes inside transaction) ──
                if ($drawJustClosed && ! $isAirtime) {
                    $base             = $this->resolvePrizePoints($survey);
                    $consolationAward = max(1, (int) round($base * 0.20));

                    $unspunForConsolation = PrizeDrawEntry::with('user')
                        ->where('survey_id', $survey->id)
                        ->where('user_id', '!=', $user->id)
                        ->where('has_spun', false)
                        ->get();

                    foreach ($unspunForConsolation as $unspun) {
                        /** @var PrizeDrawEntry $unspun */
                        $unspun->update(['has_spun' => true, 'is_winner' => false]);
                        $loserWallet = $unspun->user->wallet ?? $unspun->user->wallet()->create();
                        $loserWallet->increment('points', $consolationAward);
                        $loserWallet->transactions()->create([
                            'type'   => 'earn',
                            'points' => $consolationAward,
                            'status' => 'completed',
                            'meta'   => ['type' => 'prize_draw_consolation', 'survey_id' => $survey->id],
                        ]);
                    }
                }
            });
        } catch (\RuntimeException $e) {
            $lock->release();
            if ($e->getMessage() === 'already_spun') {
                return response()->json(['error' => 'You have already spun the slot machine!'], 409);
            }
            throw $e;
        } finally {
            $lock->release();
        }

        // ── Send airtime outside transaction to avoid rollback on API failure ──
        if ($isWinner && $isAirtime) {
            $amount = $assignedPrize ? (float) $assignedPrize['amount'] : (float) $survey->reward_amount;
            $this->dispatchAirtime($user, $survey, $amount);
        }

        if ($isWinner) {
            Log::info('Slot machine winner', [
                'user_id'     => $user->id,
                'survey_id'   => $survey->id,
                'reward_type' => $survey->reward_type,
                'points'      => $prizePoints,
                'airtime'     => $airtimeAmount,
                'prize'       => $assignedPrize,
            ]);

            $user->notify(new PrizeDrawWinnerNotification($survey, $prizePoints ?: 0));
        }

        // ── Notify losers after draw closes (notifications stay outside transaction) ──
        if ($drawJustClosed && ! $isAirtime) {
            $winnerAlias      = 'User #***' . substr((string) $user->id, -3);
            $base             = $this->resolvePrizePoints($survey);
            $consolationAward = max(1, (int) round($base * 0.20));

            $loserUsers = PrizeDrawEntry::with('user')
                ->where('survey_id', $survey->id)
                ->where('user_id', '!=', $user->id)
                ->get()
                ->pluck('user')
                ->filter();

            NotificationFacade::send($loserUsers, new PrizeDrawResultNotification($survey, $winnerAlias, $consolationAward));
        }

        $currency = config('services.africastalking.currency', 'ZMW');

        return response()->json([
            'win'               => $isWinner,
            'rewardType'        => $survey->reward_type,
            'prizePoints'       => $prizePoints,
            'consolationPoints' => $consolationPoints,
            'airtimeAmount'     => $airtimeAmount,
            'prizeName'         => $prizeName,
            'prizeSnapshot'     => $assignedPrize,
            'message'           => $isWinner
                ? ($isAirtime
                    ? "Congratulations! {$currency} {$airtimeAmount} airtime will be sent to your phone!"
                    : "Congratulations! You won {$prizePoints} points!")
                : "Sorry, you didn't win this time. {$consolationPoints} consolation points have been added to your wallet.",
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /** Points for a specific prize entry from the prizes array */
    private function prizeMoney(array $prize, Survey $survey): int
    {
        $points = (int) ($prize['points'] ?? 0);
        if ($points <= 0) {
            $points = (int) round((float) ($prize['amount'] ?? 0) * 10);
        }
        if ($points <= 0) {
            $points = $this->resolvePrizePoints($survey);
        }

        return $points;
    }

    /** Fallback prize points from survey-level fields */
    private function resolvePrizePoints(Survey $survey): int
    {
        $points = $survey->reward_points > 0
            ? $survey->reward_points
            : (int) round(($survey->reward_amount ?? 0) * 10);

        return $points > 0 ? $points : (int) config('app.prize_draw_win_points', 200);
    }

    private function awardPoints($user, Survey $survey, int $points, string $type): void
    {
        $wallet = $user->wallet ?? $user->wallet()->create();
        $wallet->increment('points', $points);
        $wallet->transactions()->create([
            'type'   => 'earn',
            'points' => $points,
            'status' => 'completed',
            'meta'   => ['type' => $type, 'survey_id' => $survey->id],
        ]);
    }

    private function dispatchAirtime($user, Survey $survey, float $amount): bool
    {
        try {
            $username = config('services.africastalking.username');
            $key      = config('services.africastalking.key');

            if ($username && $key && $amount > 0) {
                $at = new AfricasTalking($username, $key);
                $at->airtime()->send([
                    'recipients' => [[
                        'phoneNumber' => $user->phone_number,
                        'amount'      => config('services.africastalking.currency', 'ZMW') . ' ' . $amount,
                    ]],
                ]);
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Airtime dispatch failed after slot machine win', [
                'user_id'   => $user->id,
                'survey_id' => $survey->id,
                'amount'    => $amount,
                'error'     => $e->getMessage(),
            ]);

            return false;
        }
    }
}
