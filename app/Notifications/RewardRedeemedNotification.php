<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\WebPushMessage;
use Illuminate\Notifications\Notification;

class RewardRedeemedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $points,
        public float $amount
    ) {}

    public function via(object $notifiable): array
    {
        return ['webpush', 'database'];
    }

    public function toWebPush(object $notifiable, ?object $notification = null): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Reward redeemed')
            ->body("We've sent airtime worth {$this->amount}.")
            ->icon('/icon-192x192.png')
            ->data(['url' => '/wallet', 'points' => $this->points]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Reward redeemed',
            'body' => "We've sent airtime worth {$this->amount}.",
            'url' => '/wallet',
            'points' => $this->points,
        ];
    }
}
