<?php

namespace App\Notifications;

use App\Models\Survey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\WebPushMessage;
use Illuminate\Notifications\Notification;

class PrizeDrawResultNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Survey $survey,
        public string $winnerAlias,
        public int $consolationPoints
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'webpush'];
    }

    public function toWebPush(object $notifiable, ?object $notification = null): WebPushMessage
    {
        $body = $this->consolationPoints > 0
            ? "The draw for '{$this->survey->title}' has closed. {$this->winnerAlias} won! You received {$this->consolationPoints} consolation points."
            : "The draw for '{$this->survey->title}' has closed. {$this->winnerAlias} took the prize!";

        return (new WebPushMessage)
            ->title('Prize Draw Closed')
            ->body($body)
            ->icon('/icon-192x192.png')
            ->data(['url' => '/wallet', 'survey_id' => $this->survey->id]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'               => 'prize_draw_result',
            'survey_id'          => $this->survey->id,
            'title'              => 'Prize Draw Closed',
            'message'            => $this->consolationPoints > 0
                ? "The draw for '{$this->survey->title}' has closed. {$this->winnerAlias} won! You received {$this->consolationPoints} consolation points for participating."
                : "The draw for '{$this->survey->title}' has closed. {$this->winnerAlias} took the prize!",
            'consolation_points' => $this->consolationPoints,
            'url'                => '/wallet',
        ];
    }
}
