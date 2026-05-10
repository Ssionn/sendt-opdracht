<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Exception as ExceptionModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;
use Illuminate\Notifications\Slack\SlackMessage;

class ExceptionOccurredChannel extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ExceptionModel $exception,
    ) {}

    public function via(object $notifiable): array
    {
        return ["slack"];
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $truncatedTrace = mb_substr((string) $this->exception->stack_trace, 0, 500);

        $link = url("/?exception={$this->exception->id}");

        return (new SlackMessage())
            ->text(":rotating_light: *New exception in FaultLine*")
            ->headerBlock("Exception Occurred")
            ->sectionBlock(function (SectionBlock $block): void {
                $block->text("*Message:* {$this->exception->message}");
            })
            ->sectionBlock(function (SectionBlock $block): void {
                $block->field("*Type:* {$this->exception->type}");
                $block->field("*URL:* {$this->exception->url}");
            })
            ->sectionBlock(function (SectionBlock $block): void {
                $block->field("*File:* {$this->exception->file}");
                $block->field("*Line:* {$this->exception->line}");
            })
            ->sectionBlock(function (SectionBlock $block) use ($truncatedTrace): void {
                $block->text("*Stack Trace:*
```{$truncatedTrace}```");
            })
            ->actionsBlock(function ($actions) use ($link): void {
                $actions->button("View Exception")->url($link)->primary();
            })
            ->dividerBlock();
    }
}
