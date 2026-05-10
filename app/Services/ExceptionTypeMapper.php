<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ExceptionType;
use App\Exceptions\FaultlineException;

class ExceptionTypeMapper
{
    private const CLASSES = [
        ExceptionType::Domain->value         => FaultlineException::class,
        ExceptionType::Runtime->value        => \RuntimeException::class,
        ExceptionType::Logic->value          => \LogicException::class,
        ExceptionType::InvalidArgument->value => \InvalidArgumentException::class,
        ExceptionType::BadMethod->value      => \BadMethodCallException::class,
    ];

    private const DEFAULTS = [
        ExceptionType::Domain->value         => 'Operation not permitted: user attempted an action that violates a business rule.',
        ExceptionType::Runtime->value        => 'A runtime error occurred during execution.',
        ExceptionType::Logic->value          => 'A logic error was detected in the application.',
        ExceptionType::InvalidArgument->value => 'An invalid argument was supplied to an operation.',
        ExceptionType::BadMethod->value      => 'A call was made to an undefined or inaccessible method.',
    ];

    public function toClass(ExceptionType $type): string
    {
        return self::CLASSES[$type->value];
    }

    public function toDefaultMessage(ExceptionType $type): string
    {
        return self::DEFAULTS[$type->value];
    }
}
