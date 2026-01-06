<?php

declare(strict_types=1);

namespace App\ValueObjects\Identification;

use App\Exceptions\InvalidValueObjectConversion\Identification\InvalidEmail;

class Email
{
    public function __construct(private string $email)
    {
        $this->email = strtolower($this->email);
        $this->email = trim($this->email);
        $this->validate($this->email);
    }

    public function getValue(): string
    {
        return $this->email;
    }

    public function __toString(): string
    {
        return $this->email;
    }

    public function validate(string  $value): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmail($value);
        }

    }
}
