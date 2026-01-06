<?php

declare(strict_types=1);

namespace App\Exceptions\InvalidValueObjectConversion\Money;

use InvalidArgumentException;

class InvalidMoney extends InvalidArgumentException
{
    public function __construct(string $value)
    {
        parent::__construct("Invalid money amount: {$value}. Amount must be a non-negative numeric value.");
    }
}
