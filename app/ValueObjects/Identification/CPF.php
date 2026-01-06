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

    public static function generate(): self
    {
        $digits = '';
        for ($i = 0; $i < 9; $i++) {
            $digits .= mt_rand(0, 9);
        }

        $digits .= self::calculateCheckDigit($digits, 10);
        $digits .= self::calculateCheckDigit($digits, 11);

        return new self($digits);
    }

    private static function calculateCheckDigit(string $base, int $factor): int
    {
        $sum = 0;
        for ($i = 0; $i < strlen($base); $i++) {
            $sum += intval($base[$i]) * ($factor - $i);
        }

        $remainder = ($sum * 10) % 11;

        return $remainder >= 10 ? 0 : $remainder;
    }
}
