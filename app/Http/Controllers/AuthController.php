<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function showLogin(): RedirectResponse
    {
        return redirect('/');
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['success' => true, 'message' => 'Account created.']);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $request->session()->flash('status', 'Logged in successfully.');

            return response()->json([
                'success' => true,
                'message' => 'Logged in successfully.',
            ]);
        }

        $request->session()->flash('error', 'The provided credentials do not match our records.');

        return response()->json([
            'success' => false,
            'message' => 'The provided credentials do not match our records.',
        ], 401);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
        ]);
    }

    public function redirectToSlack(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return Socialite::driver('slack')->redirect();
    }

    public function handleSlackCallback(): RedirectResponse
    {
        $socialiteUser = Socialite::driver('slack')->user();

        $user = User::updateOrCreate(
            [
                'provider' => 'slack',
                'provider_id' => $socialiteUser->getId(),
            ],
            [
                'name' => $socialiteUser->getName(),
                'email' => $socialiteUser->getEmail() ?? '',
                'avatar' => $socialiteUser->getAvatar(),
            ]
        );

        Auth::login($user);

        return redirect('/');
    }
}
