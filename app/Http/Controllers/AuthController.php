<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $this->authService->register($request->validated());

        return response()->json(['success' => true, 'message' => 'Account created.']);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if ($this->authService->login($request->validated())) {
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

    public function logout(Request $request): RedirectResponse
    {
        $this->authService->logout($request);

        return redirect('/');
    }

    public function redirectToSlack(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return Socialite::driver('slack-openid')->redirect();
    }

    public function handleSlackCallback(): RedirectResponse
    {
        $this->authService->handleSlackCallback();

        return redirect('/');
    }
}
