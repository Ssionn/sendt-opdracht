<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ExceptionType;
use App\Http\Requests\Exception\TriggerExceptionRequest;
use App\Models\Exception as ExceptionModel;
use App\Models\User;
use App\Services\ExceptionService;
use App\Services\ExceptionTypeMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ExceptionController extends Controller
{
    public function __construct(
        private readonly ExceptionService $exceptionService,
        private readonly ExceptionTypeMapper $mapper,
    ) {}

    public function trigger(TriggerExceptionRequest $request): JsonResponse
    {
        $type = ExceptionType::from($request->validated('type'));
        $message = $request->validated('message') ?? $this->mapper->toDefaultMessage($type);
        $class = $this->mapper->toClass($type);

        $exception = new $class($message);

        report($exception);

        return response()->json(['success' => true, 'message' => $message]);
    }

    public function index(): JsonResponse
    {
        return response()->json(
            $this->exceptionService->forUser(Auth::id())
        );
    }

    public function show(ExceptionModel $exception): JsonResponse
    {
        if ($exception->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json($exception);
    }

    public function destroy(ExceptionModel $exception): JsonResponse
    {
        if ($exception->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $this->exceptionService->delete($exception);

        return response()->json(['success' => true]);
    }

    public function resolve(ExceptionModel $exception): JsonResponse
    {
        if ($exception->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $resolveInSentry = (bool) request()->input('resolve_in_sentry', false);

        /** @var User $user */
        $user = Auth::user();

        $sentryResolved = false;
        $sentryError = null;
        
        if ($resolveInSentry && $exception->sent_to_sentry) {
            $result = $this->exceptionService->resolveInSentry($exception, $user);
            
            $sentryResolved = $result['success'];
            $sentryError = $result['message'];
        }

        $this->exceptionService->resolve($exception);

        return response()->json([
            'success' => true,
            'sentry_resolved' => $sentryResolved,
            'sentry_error' => $sentryError,
        ]);
    }
}
