<?php

declare(strict_types=1);

namespace App\Enums;

enum AuditStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
}
