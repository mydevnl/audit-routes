<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Enums;

enum ExitCode: int
{
    case Success = 0;
    case Failure = 1;
    case Invalid = 2;
}
