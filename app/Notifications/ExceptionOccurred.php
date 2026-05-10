<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Exception as ExceptionModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExceptionOccurred extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ExceptionModel $exception,
    ) {}

    public function via(object $notifiable): array
    {
        return ["mail"];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("[FaultLine] New exception occurred")
            ->line("A new exception was recorded in your application.")
            ->action("View Dashboard", url("/"));
    }
}
