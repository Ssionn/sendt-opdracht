<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Exception as ExceptionModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ExceptionService
{
    public function __construct(
        private readonly SentryApiService $sentryApiService,
    ) {}

    public function forUser(int $userId): Collection
    {
        return ExceptionModel::where('user_id', $userId)->latest()->get();
    }

    public function find(int $userId, int $id): ?ExceptionModel
    {
        return ExceptionModel::where('user_id', $userId)->find($id);
    }

    public function delete(ExceptionModel $exception): void
    {
        $exception->delete();
    }

    public function resolve(ExceptionModel $exception): void
    {
        $exception->resolve();
    }

    /**
     * @return array{success: bool, message: ?string, status: ?int}
     */
    public function resolveInSentry(ExceptionModel $exception, User $user): array
    {
        return $this->sentryApiService->resolveIssue($exception, $user);
    }
}
