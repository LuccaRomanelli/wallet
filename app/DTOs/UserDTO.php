<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\UserType;
use App\ValueObjects\Identification\CNPJ;
use App\ValueObjects\Identification\CPF;
use App\ValueObjects\Money\Money;

readonly class UserDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public CPF|CNPJ $document,
        public UserType $userType,
        public Money $startMoney,
    ) {}
}
