<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'provider', 'provider_id', 'avatar', 'notify_email', 'notify_slack', 'notify_sentry', 'slack_bot_token', 'slack_channel', 'sentry_auth_token', 'sentry_org_slug', 'sentry_project_slug', 'sentry_region', 'sentry_dsn'])]
#[Hidden(['password', 'remember_token', 'sentry_auth_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notify_email' => 'boolean',
            'notify_slack' => 'boolean',
            'notify_sentry' => 'boolean',
            'slack_bot_token' => 'encrypted',
            'sentry_auth_token' => 'encrypted',
            'sentry_dsn' => 'encrypted',
        ];
    }

    public function slackBotToken(): string
    {
        return $this->slack_bot_token;
    }
}
