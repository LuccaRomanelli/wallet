<?php

declare(strict_types=1);

namespace App\Exceptions\Transfer;

use Exception;

class SelfTransferException extends Exception
{
    public function __construct()
    {
        parent::__construct('Cannot transfer to yourself.');
    }
}
