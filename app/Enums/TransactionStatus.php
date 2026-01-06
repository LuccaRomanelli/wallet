<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Completed = 'completed';
    case Failed = 'failed';
}
