<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SettingsService
{
    public function update(
        User $user,
        bool $notifyEmail,
        bool $notifySlack,
        bool $notifySentry,
        ?string $sentryAuthToken = null,
        ?string $sentryOrgSlug = null,
        ?string $sentryProjectSlug = null,
        ?string $sentryRegion = null,
        ?string $sentryDsn = null,
    ): void {
        $data = [
            'notify_email' => $notifyEmail,
            'notify_slack' => $notifySlack,
            'notify_sentry' => $notifySentry,
        ];

        if ($sentryAuthToken !== null) {
            $data['sentry_auth_token'] = $sentryAuthToken;
        }

        if ($sentryOrgSlug !== null) {
            $data['sentry_org_slug'] = $sentryOrgSlug;
        }

        if ($sentryProjectSlug !== null) {
            $data['sentry_project_slug'] = $sentryProjectSlug;
        }

        if ($sentryRegion !== null) {
            $data['sentry_region'] = $sentryRegion;
        }

        if ($sentryDsn !== null) {
            $data['sentry_dsn'] = $sentryDsn;
        }

        $user->update($data);
    }

    public function setPassword(User $user, string $password): void
    {
        $user->update(['password' => Hash::make($password)]);
    }
}
