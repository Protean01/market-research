<?php

namespace App\Notifications;

use App\Models\Survey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\WebPushMessage;
use Illuminate\Notifications\Notification;

class PrizeDrawWinnerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Survey $survey,
        public int $prizePoints
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['webpush', 'database'];
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush(object $notifiable, ?object $notification = null): WebPushMessage
    {
        $isAirtime = $this->survey->reward_type === 'airtime';
        $body = $isAirtime
            ? "Your {$this->survey->reward_amount} airtime from '{$this->survey->title}' is on its way to your phone!"
            : "You won {$this->prizePoints} points in the '{$this->survey->title}' prize draw!";

        return (new WebPushMessage)
            ->title('Congratulations! You Won!')
            ->body($body)
            ->icon('/icon-192x192.png')
            ->data([
                'url' => $isAirtime ? '/dashboard' : '/wallet',
                'survey_id' => $this->survey->id,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $isAirtime = $this->survey->reward_type === 'airtime';

        return [
            'type' => 'prize_draw_winner',
            'survey_id' => $this->survey->id,
            'title' => 'Congratulations! You Won!',
            'message' => $isAirtime
                ? "Your {$this->survey->reward_amount} airtime from '{$this->survey->title}' is being sent to your phone!"
                : "You won {$this->prizePoints} points in the '{$this->survey->title}' prize draw. Points have been added to your wallet!",
            'url' => $isAirtime ? '/dashboard' : '/wallet',
        ];
    }
}
