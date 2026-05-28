<?php

namespace App\Notifications;

use App\Models\Survey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\WebPushMessage;
use Illuminate\Notifications\Notification;

class PrizeDrawInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public Survey $survey;

    /**
     * Create a new notification instance.
     */
    public function __construct(Survey $survey)
    {
        $this->survey = $survey;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['webpush', 'database'];
    }

    public function toWebPush(object $notifiable, ?object $notification = null): WebPushMessage
    {
        $isAirtime = $this->survey->reward_type === 'airtime';

        return (new WebPushMessage)
            ->title($isAirtime ? 'Claim Your Airtime!' : 'Prize Draw Open!')
            ->body($isAirtime
                ? "Spin now to claim your {$this->survey->reward_amount} airtime from '{$this->survey->title}'!"
                : "The prize draw for '{$this->survey->title}' is open! Spin the slot machine now.")
            ->icon('/icon-192x192.png')
            ->data([
                'url' => route('survey.slot-machine', $this->survey->id),
                'survey_id' => $this->survey->id,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $isAirtime = $this->survey->reward_type === 'airtime';

        return [
            'type' => 'prize_draw_invitation',
            'survey_id' => $this->survey->id,
            'title' => $isAirtime ? 'Claim Your Airtime!' : 'Prize Draw Open!',
            'message' => $isAirtime
                ? "Spin to claim your {$this->survey->reward_amount} airtime from '{$this->survey->title}'!"
                : "The prize draw for '{$this->survey->title}' is open! Spin the slot machine to see if you win.",
            'url' => route('survey.slot-machine', $this->survey->id),
        ];
    }
}
