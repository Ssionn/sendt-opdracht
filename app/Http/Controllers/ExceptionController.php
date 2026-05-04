<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\FaultlineException;
use App\Models\Exception as ExceptionModel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ExceptionController extends Controller
{
    public function trigger(): RedirectResponse
    {
        throw new FaultlineException('Test exception triggered from FaultLine at '.now());
    }

    public function index(): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([]);
        }

        return response()->json(
            ExceptionModel::where('user_id', Auth::id())->latest()->get()
        );
    }

    public function showView(): View|RedirectResponse
    {
        return view('welcome', [
            'exceptions' => ExceptionModel::where('user_id', Auth::id())->latest()->get(),
        ]);
    }
}
