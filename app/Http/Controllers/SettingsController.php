<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Settings\SetPasswordRequest;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SettingsController extends Controller
{
    public function __construct(private readonly SettingsService $settingsService) {}

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $data = $request->validated();

        /** @var User $user */
        $user = auth()->user();

        $this->settingsService->update(
            $user,
            $data['notify_email'],
            $data['notify_slack'],
            $data['notify_sentry'],
            $data['sentry_auth_token'] ?? null,
            $data['sentry_org_slug'] ?? null,
            $data['sentry_project_slug'] ?? null,
            $data['sentry_region'] ?? null,
            $data['sentry_dsn'] ?? null,
        );

        return response()->json(['success' => true]);
    }

    public function setPassword(SetPasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $this->settingsService->setPassword($user, $request->validated('password'));

        return response()->json(['success' => true]);
    }

    public function connectSlack(): RedirectResponse
    {
        return Socialite::driver('slack-openid')->redirect();
    }

    public function testSlackToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'channel' => ['nullable', 'string'],
        ]);

        $response = Http::withToken($request->token)->get('https://slack.com/api/auth.test');

        if ($response->json('ok')) {
            auth()->user()->update([
                'slack_bot_token' => $request->token,
                'slack_channel' => $request->channel,
            ]);

            return response()->json(['success' => true, 'team' => $response->json('team')]);
        }

        return response()->json([
            'success' => false,
            'message' => $response->json('error') ?? 'Invalid token',
        ], 422);
    }
}
