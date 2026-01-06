<?php

declare(strict_types=1);

namespace App\Exceptions\Transfer;

use Exception;

class InsufficientBalanceException extends Exception
{
    public function __construct()
    {
        parent::__construct('Insufficient balance to complete the transfer.');
    }
}
