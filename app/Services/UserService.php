<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserType;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\ValueObjects\Money\Money;

class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function createUser(
        string $name,
        string $email,
        string $password,
        string $document,
        Money $startMoney
    ): User {
        return $this->userRepository->create(
            name: $name,
            email: $email,
            password: $password,
            document: $document,
            userType: UserType::Common,
            startMoney: $startMoney
        );
    }

    public function createStore(
        string $name,
        string $email,
        string $password,
        string $document,
        Money $startMoney
    ): User {
        return $this->userRepository->create(
            name: $name,
            email: $email,
            password: $password,
            document: $document,
            userType: UserType::Merchant,
            startMoney: $startMoney
        );
    }
}
