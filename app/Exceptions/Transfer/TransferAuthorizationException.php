<?php

declare(strict_types=1);

namespace App\Exceptions\Transfer;

use Exception;

class TransferAuthorizationException extends Exception
{
    public function __construct(string $message = 'Transfer not authorized by external service.')
    {
        parent::__construct($message);
    }
}
