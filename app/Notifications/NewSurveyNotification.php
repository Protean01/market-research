<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\WebPushMessage;
use Illuminate\Notifications\Notification;

class NewSurveyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public int $surveyId,
        public ?string $description = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['webpush', 'database'];
    }

    public function toWebPush(object $notifiable, ?object $notification = null): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('New survey available')
            ->body($this->title)
            ->icon('/icon-192x192.png')
            ->data([
                'url' => '/surveys/' . $this->surveyId,
                'subtitle' => $this->description,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New survey available',
            'body' => $this->title,
            'url' => '/surveys/' . $this->surveyId,
            'survey_id' => $this->surveyId,
        ];
    }
}
