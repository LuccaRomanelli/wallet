<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\UserDTO;
use App\Enums\UserType;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\ValueObjects\Money\Money;

class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function createAccount(
        string $name,
        string $email,
        string $password,
        string $document,
        UserType $userType,
        Money $startMoney
    ): UserDTO {
        return $this->userRepository->create(
            name: $name,
            email: $email,
            password: $password,
            document: $document,
            userType: $userType,
            startMoney: $startMoney
        );
    }
}
