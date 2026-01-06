<?php

declare(strict_types=1);

namespace App\Exceptions\InvalidValueObjectConversion\Identification;

use InvalidArgumentException;

class InvalidCNPJ extends InvalidArgumentException
{
    public function __construct(string $value)
    {
        parent::__construct("Invalid CNPJ: {$value}");
    }
}
