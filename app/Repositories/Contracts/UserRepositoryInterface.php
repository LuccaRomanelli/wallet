<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\DTOs\UserDTO;
use App\Enums\UserType;
use App\ValueObjects\Money\Money;

interface UserRepositoryInterface
{
    public function find(int $id): ?UserDTO;

    public function findWithLock(int $id): ?UserDTO;

    public function getStartMoney(int $userId): Money;

    public function create(
        string $name,
        string $email,
        string $password,
        string $document,
        UserType $userType,
        Money $startMoney
    ): UserDTO;
}
