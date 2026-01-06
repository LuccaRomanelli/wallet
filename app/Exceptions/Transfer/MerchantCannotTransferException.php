<?php

declare(strict_types=1);

namespace App\Exceptions\Transfer;

use Exception;

class MerchantCannotTransferException extends Exception
{
    public function __construct()
    {
        parent::__construct('Merchants are not allowed to send transfers.');
    }
}
