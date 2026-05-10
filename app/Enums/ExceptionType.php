<?php

declare(strict_types=1);

namespace App\Enums;

enum ExceptionType: string
{
    case Domain         = 'domain';
    case Runtime        = 'runtime';
    case Logic          = 'logic';
    case InvalidArgument = 'invalid_argument';
    case BadMethod      = 'bad_method';
}
