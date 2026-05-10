<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Exception as ExceptionModel;
use App\Notifications\ExceptionOccurred;
use App\Notifications\ExceptionOccurredChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Notifications\Slack\SlackRoute;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendExceptionNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ExceptionModel $exception) {}

    public function handle(): void
    {
        $user = $this->exception->user;

        $this->handleSlack($user);
        $this->handleMail($user);
    }

    private function handleSlack(?object $user): void
    {
        if ($user === null) {
            return;
        }

        $userWantsSlack = $user->notify_slack;
        $token = $user->slack_bot_token;
        $channel = $user->slack_channel;

        if (! $userWantsSlack || ! $token || ! $channel) {
            return;
        }

        Notification::route('slack', SlackRoute::make($channel, $token))
            ->notify(new ExceptionOccurredChannel($this->exception));

        try {
            $this->exception->update(['sent_to_slack' => true]);
        } catch (Throwable) {
            //
        }
    }

    private function handleMail(?object $user): void
    {
        if ($user === null) {
            Notification::route('mail', config('mail.from.address'))
                ->notify(new ExceptionOccurred($this->exception));
        } elseif ($user->notify_email) {
            $user->notify(new ExceptionOccurred($this->exception));
        } else {
            return;
        }

        try {
            $this->exception->update(['sent_to_mail' => true]);
        } catch (Throwable) {
            //
        }
    }
}
