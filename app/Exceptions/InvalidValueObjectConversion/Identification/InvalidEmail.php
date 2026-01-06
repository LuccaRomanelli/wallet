<?php

declare(strict_types=1);

namespace App\Exceptions\InvalidValueObjectConversion\Identification;

use InvalidArgumentException;

class InvalidEmail extends InvalidArgumentException
{
    public function __construct(string $value)
    {
        parent::__construct("Invalid email: {$value}");
    }
}
