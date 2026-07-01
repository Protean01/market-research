<?php

namespace App\Http\Controllers;

use AfricasTalking\SDK\AfricasTalking;
use App\Models\PrizeDrawEntry;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $user   = $request->user();
        $wallet = $user->wallet ?? $user->wallet()->create(['balance' => 0, 'points' => 0]);

        $transactions = $wallet->transactions()->latest()->get();
        $totalEarned  = $wallet->transactions()->where('type', 'earn')->sum('points');

        return Inertia::render('Wallet', [
            'points'       => $wallet->points,
            'total_earned' => $totalEarned,
            'transactions' => $transactions,
            'draw_entries' => $user->prizeDrawEntries()->with('survey:id,title,reward_type,reward_amount')->latest()->get(),
            'won_prizes'   => $user->prizeDrawEntries()->where('is_winner', true)->with('survey:id,title,reward_type')->get(),
        ]);
    }

    public function json(Request $request)
    {
        $user   = $request->user();
        $wallet = $user->wallet ?? $user->wallet()->create(['balance' => 0, 'points' => 0]);

        return response()->json([
            'points'       => $wallet->points,
            'balance'      => $wallet->balance,
            'total_earned' => $wallet->transactions()->where('type', 'earn')->sum('points'),
            'transactions' => $wallet->transactions()->latest()->get(),
            'draw_entries' => $user->prizeDrawEntries()->with('survey:id,title,reward_type,reward_amount')->latest()->get(),
            'won_prizes'   => $user->prizeDrawEntries()->where('is_winner', true)->with('survey:id,title,reward_type')->get(),
        ]);
    }

    public function balance(Request $request)
    {
        $user   = $request->user();
        $wallet = $user->wallet ?? $user->wallet()->create(['balance' => 0, 'points' => 0]);

        return response()->json([
            'points'  => $wallet->points,
            'balance' => $wallet->balance,
        ]);
    }

    public function redeem(Request $request)
    {
        $request->validate([
            'points' => ['required', 'integer', 'min:1'],
        ]);

        $user = $request->user();
        if (! $user->phone_number) {
            return response()->json(['error' => 'No phone number on your account. Please update your profile first.'], 422);
        }

        $pointsToRedeem = (int) $request->input('points');

        // Conversion: 10 points = 1 unit of currency (e.g. ZMW 1)
        $airtimeAmount = round($pointsToRedeem / 10, 2);
        $currency      = config('services.africastalking.currency', 'ZMW');

        $transaction = null;
        $walletObj = null;

        try {
            DB::transaction(function () use ($user, $pointsToRedeem, $airtimeAmount, &$transaction, &$walletObj) {
                // Lock the user row to serialize user-scoped wallet/transaction updates
                \App\Models\User::where('id', $user->id)->lockForUpdate()->first();

                $wallet = $user->wallet()->lockForUpdate()->first();
                if (! $wallet) {
                    $wallet = $user->wallet()->create(['balance' => 0, 'points' => 0]);
                    $wallet = $user->wallet()->lockForUpdate()->first();
                }

                if ($wallet->points < $pointsToRedeem) {
                    throw new \RuntimeException('Insufficient points.');
                }

                $wallet->decrement('points', $pointsToRedeem);

                $transaction = $wallet->transactions()->create([
                    'type'   => 'redeem',
                    'points' => $pointsToRedeem,
                    'status' => 'processing',
                    'meta'   => [
                        'type'   => 'airtime',
                        'amount' => $airtimeAmount,
                    ],
                ]);

                $walletObj = $wallet;
            });
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $sent = $this->sendAirtime($user->phone_number, $currency, $airtimeAmount, $user->id, $transaction->id);

        $transaction->update(['status' => $sent ? 'completed' : 'failed']);

        if (! $sent) {
            // Refund points if delivery failed
            DB::transaction(function () use ($walletObj, $pointsToRedeem, $transaction) {
                $lockedWallet = \App\Models\Wallet::where('id', $walletObj->id)->lockForUpdate()->first();
                $lockedWallet->increment('points', $pointsToRedeem);
                $lockedWallet->transactions()->create([
                    'type'   => 'earn',
                    'points' => $pointsToRedeem,
                    'status' => 'completed',
                    'meta'   => ['type' => 'airtime_refund', 'original_transaction_id' => $transaction->id],
                ]);
            });

            return response()->json(['error' => 'Airtime delivery failed. Your points have been refunded.'], 500);
        }

        return response()->json([
            'message' => "Success! {$currency} {$airtimeAmount} airtime sent to {$user->phone_number}.",
            'points'  => $walletObj->fresh()->points,
        ]);
    }

    public function enterDraw(Request $request)
    {
        $request->validate([
            'survey_id' => ['required', 'integer', 'exists:surveys,id'],
        ]);

        $user   = $request->user();
        $survey = Survey::findOrFail($request->input('survey_id'));

        if ($survey->reward_type !== 'prize_draw') {
            return response()->json(['error' => 'Prize draw is not enabled for this survey.'], 403);
        }

        $entryCost  = (int) config('app.prize_draw_entry_cost', 50);

        if ($entryCost <= 0) {
            return response()->json(['error' => 'Entry cost is not configured.'], 422);
        }

        if (! $user->responses()->where('survey_id', $survey->id)->exists()) {
            return response()->json(['error' => 'You must complete this survey before entering the draw.'], 403);
        }

        if (PrizeDrawEntry::where('survey_id', $survey->id)->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'You have already entered this prize draw.'], 409);
        }

        $walletObj = null;

        try {
            DB::transaction(function () use ($user, $survey, $entryCost, &$walletObj) {
                // Lock the user row to serialize user-scoped wallet/transaction updates
                \App\Models\User::where('id', $user->id)->lockForUpdate()->first();

                $wallet = $user->wallet()->lockForUpdate()->first();
                if (! $wallet) {
                    $wallet = $user->wallet()->create(['balance' => 0, 'points' => 0]);
                    $wallet = $user->wallet()->lockForUpdate()->first();
                }

                if ($wallet->points < $entryCost) {
                    throw new \RuntimeException('Insufficient points.');
                }

                $wallet->decrement('points', $entryCost);
                $wallet->transactions()->create([
                    'type'   => 'redeem',
                    'points' => $entryCost,
                    'status' => 'completed',
                    'meta'   => [
                        'type'         => 'prize_draw',
                        'survey_id'    => $survey->id,
                        'survey_title' => $survey->title,
                    ],
                ]);
                PrizeDrawEntry::create([
                    'survey_id'      => $survey->id,
                    'user_id'        => $user->id,
                    'points_entered' => $entryCost,
                ]);

                $walletObj = $wallet;
            });
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'You have been entered into the prize draw!',
            'points'  => $walletObj->fresh()->points,
        ]);
    }

    private function sendAirtime(string $phone, string $currency, float $amount, int $userId, int $transactionId): bool
    {
        try {
            $username = config('services.africastalking.username');
            $key      = config('services.africastalking.key');

            if (! $username || ! $key || $amount <= 0) {
                Log::warning('Airtime not sent - missing config or zero amount', compact('userId', 'transactionId'));
                return false;
            }

            $at = new AfricasTalking($username, $key);
            $at->airtime()->send([
                'recipients' => [[
                    'phoneNumber' => $phone,
                    'amount'      => "{$currency} {$amount}",
                ]],
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Airtime dispatch failed', [
                'user_id'        => $userId,
                'transaction_id' => $transactionId,
                'error'          => $e->getMessage(),
            ]);

            return false;
        }
    }
}
