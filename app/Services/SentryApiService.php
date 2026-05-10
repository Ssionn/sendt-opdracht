<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Exception as ExceptionModel;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class SentryApiService
{
    /**
     * @return array{success: bool, message: ?string, status: ?int}
     */
    public function resolveIssue(ExceptionModel $exception, User $user): array
    {
        if (
            ! $exception->sentry_event_id ||
            ! $user->sentry_auth_token ||
            ! $user->sentry_org_slug ||
            ! $user->sentry_project_slug
        ) {
            return [
                'success' => false,
                'message' => 'Missing Sentry credentials. Provide an auth token, organisation and project slug in settings.',
                'status' => null,
            ];
        }

        $baseUrl = $this->baseUrlForRegion($user->sentry_region);

        $eventResponse = Http::withToken($user->sentry_auth_token)
            ->get("{$baseUrl}/projects/{$user->sentry_org_slug}/{$user->sentry_project_slug}/events/{$exception->sentry_event_id}/");

        if (! $eventResponse->successful()) {
            return [
                'success' => false,
                'message' => $this->messageForStatus(
                    $eventResponse->status(),
                    "Couldn't find the event in Sentry. Check the org/project slug and region.",
                ),
                'status' => $eventResponse->status(),
            ];
        }

        $issueId = $eventResponse->json('groupID');

        if (! $issueId) {
            return [
                'success' => false,
                'message' => 'Sentry returned no group ID for this event.',
                'status' => $eventResponse->status(),
            ];
        }

        $resolveResponse = Http::withToken($user->sentry_auth_token)
            ->put("{$baseUrl}/organizations/{$user->sentry_org_slug}/issues/{$issueId}/", [
                'status' => 'resolved',
            ]);

        if ($resolveResponse->successful()) {
            return [
                'success' => true,
                'message' => null,
                'status' => $resolveResponse->status(),
            ];
        }

        return [
            'success' => false,
            'message' => $this->messageForStatus(
                $resolveResponse->status(),
                'Sentry rejected the resolve request.',
            ),
            'status' => $resolveResponse->status(),
        ];
    }

    private function baseUrlForRegion(?string $region): string
    {
        return match ($region) {
            'de' => 'https://de.sentry.io/api/0',
            default => 'https://sentry.io/api/0',
        };
    }

    private function messageForStatus(int $status, string $fallback): string
    {
        return match ($status) {
            401 => 'Sentry rejected the auth token (401). Check that it is valid and not revoked.',
            403 => 'The Sentry auth token is missing the required event:write scope (403).',
            404 => 'Sentry could not find the issue (404). Check the org/project slug and region.',
            default => $fallback,
        };
    }
}
