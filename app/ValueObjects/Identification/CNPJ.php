<?php

declare(strict_types=1);

namespace App\ValueObjects\Identification;

use App\Exceptions\InvalidValueObjectConversion\Identification\InvalidCNPJ;

class CNPJ
{
    public function __construct(private string $cnpj)
    {
        $this->cnpj = $this->normalizeCnpj($cnpj);
        $this->validate($this->cnpj);
    }

    public function getValue(): string
    {
        return $this->cnpj;
    }

    public function __toString(): string
    {
        return $this->cnpj;
    }

    private function normalizeCnpj(string $value): string
    {
        $cnpj = preg_replace('/\D/', '', $value);
        return str_pad($cnpj, 14, '0', STR_PAD_LEFT);
    }

    public function validate(string $value): void
    {
        $cnpj = preg_replace('/\D/', '', $value);

        if (preg_match('/^(\d)\1*$/', $cnpj) || strlen($cnpj) != 14) {
            throw new InvalidCNPJ($value);
        }

        if (!$this->checkDigitIsValid(12, $cnpj) || !$this->checkDigitIsValid(13, $cnpj)) {
            throw new InvalidCNPJ($value);
        }
    }

    private function checkDigitIsValid(int $position, string $cnpj): bool
    {
        $sum     = 0;
        $weights = $position === 12 ? [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2] : [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        for ($digit = 0; $digit < $position; $digit++) {
            $sum += intval($cnpj[$digit]) * $weights[$digit];
        }

        $remainder = $sum % 11;
        $remainder = $remainder < 2 ? 0 : 11 - $remainder;

        return intval($cnpj[$position]) === $remainder;
    }

    public static function generate(): self
    {
        $digits = '';
        for ($i = 0; $i < 8; $i++) {
            $digits .= mt_rand(0, 9);
        }
        $digits .= '0001';

        $digits .= self::calculateCheckDigit($digits, [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        $digits .= self::calculateCheckDigit($digits, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

        return new self($digits);
    }

    /**
     * @param array<int> $weights
     */
    private static function calculateCheckDigit(string $base, array $weights): int
    {
        $sum = 0;
        for ($i = 0; $i < strlen($base); $i++) {
            $sum += intval($base[$i]) * $weights[$i];
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}
