<?php

declare(strict_types=1);

namespace App\ValueObjects\Identification;

use App\Exceptions\InvalidValueObjectConversion\Identification\InvalidCPF;

class CPF
{
    public function __construct(private string $cpf)
    {
        $this->cpf = $this->normalizeCpf($cpf);
        $this->validate($this->cpf);
    }

    public function getValue(): string
    {
        return $this->cpf;
    }

    public function __toString(): string
    {
        return $this->cpf;
    }

    private function normalizeCpf(string $value): string
    {
        $cpf = preg_replace('/\D/', '', $value);
        return str_pad($cpf, 11, '0', STR_PAD_LEFT);
    }

    public function validate(string $value): void
    {
        $cpf = preg_replace('/\D/', '', $value);

        if (preg_match('/^(\d)\1*$/', $cpf) || strlen($cpf) != 11) {
            throw new InvalidCPF($value);
        }

        if (!$this->checkDigitIsValid(9, $cpf) || !$this->checkDigitIsValid(10, $cpf)) {
            throw new InvalidCPF($value);
        }
    }

    private function checkDigitIsValid(int $position, string $cpf): bool
    {
        $sum = 0;
        for ($digit = 0; $digit < $position; $digit++) {
            $sum += intval($cpf[$digit]) * (($position + 1) - $digit);
        }

        $remainder = ($sum * 10) % 11;

        $remainder = $remainder >= 10 ? 0 : $remainder;

        return intval($cpf[$position]) === $remainder;
    }
}
