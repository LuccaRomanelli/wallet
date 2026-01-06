<?php

declare(strict_types=1);

namespace App\ValueObjects\Money;

use App\Exceptions\InvalidValueObjectConversion\Money\InvalidMoney;

class Money
{
    private int $cents;

    public function __construct(int $cents)
    {
        $this->validate($cents);
        $this->cents = $cents;
    }

    public function getCents(): int
    {
        return $this->cents;
    }

    public function toDecimal(): string
    {
        return number_format($this->cents / 100, 2, '.', '');
    }

    public function __toString(): string
    {
        return $this->toDecimal();
    }

    private function validate(int $cents): void
    {
        if ($cents < 0) {
            throw new InvalidMoney((string) $cents);
        }
    }

    public function add(Money $other): self
    {
        return new self($this->cents + $other->getCents());
    }

    public function subtract(Money $other): self
    {
        return new self($this->cents - $other->getCents());
    }

    public function isGreaterThanOrEqual(Money $other): bool
    {
        return $this->cents >= $other->getCents();
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public static function fromDecimal(string|float $amount): self
    {
        if (is_string($amount)) {
            $amount = trim($amount);
            $amount = preg_replace('/[^\d.\-]/', '', $amount);
        }

        $cents = (int) round((float) $amount * 100);

        return new self($cents);
    }
}
