<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\UserType;
use App\Models\User;
use App\ValueObjects\Money\Money;

interface UserRepositoryInterface
{
    public function find(int $id): ?User;

    public function getStartMoney(int $userId): Money;

    public function create(
        string $name,
        string $email,
        string $password,
        string $document,
        UserType $userType,
        Money $startMoney
    ): User;
}
