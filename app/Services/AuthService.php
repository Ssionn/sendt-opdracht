<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class AuthService
{
    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user);
        session()->regenerate();

        return $user;
    }

    public function login(array $credentials): bool
    {
        if (! Auth::attempt($credentials)) {
            return false;
        }

        session()->regenerate();

        return true;
    }

    public function logout(Request $request): void
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public function handleSlackCallback(): User
    {
        $socialiteUser = Socialite::driver('slack-openid')->user();

        $raw = $socialiteUser->getRaw();
        $name = $socialiteUser->getName() ?? $socialiteUser->getNickname() ?? $raw['name'] ?? $raw['real_name'] ?? 'Slack User';
        $email = $socialiteUser->getEmail() ?? $raw['email'] ?? '';

        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();
            $user->update([
                'provider' => 'slack',
                'provider_id' => $socialiteUser->getId(),
                'avatar' => $socialiteUser->getAvatar(),
            ]);

            return $user;
        }

        $user = User::updateOrCreate(
            [
                'provider' => 'slack',
                'provider_id' => $socialiteUser->getId(),
            ],
            [
                'name' => $name,
                'email' => $email,
                'avatar' => $socialiteUser->getAvatar(),
            ]
        );

        Auth::login($user);

        return $user;
    }
}
