<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ExceptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'message', 'url', 'type', 'stack_trace', 'file', 'line', 'resolved_at', 'sent_to_mail', 'sent_to_slack', 'sent_to_sentry', 'sentry_event_id'])]
class Exception extends Model
{
    /** @use HasFactory<ExceptionFactory> */
    use HasFactory;

    public function casts(): array
    {
        return [
            'line' => 'integer',
            'resolved_at' => 'datetime',
            'sent_to_mail' => 'boolean',
            'sent_to_slack' => 'boolean',
            'sent_to_sentry' => 'boolean',
        ];
    }

    public function resolve(): void
    {
        $this->resolved_at = now();
        $this->save();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
