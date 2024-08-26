<?php

declare(strict_types=1);

namespace MyDev\AuditRoutes\Enums;

enum AuditStatus: string
{
    case Failed = 'failed';
    case Ok = 'ok';
}
