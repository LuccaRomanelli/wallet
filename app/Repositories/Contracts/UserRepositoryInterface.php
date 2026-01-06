<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;
use App\ValueObjects\Money\Money;

interface UserRepositoryInterface
{
    public function find(int $id): ?User;

    public function getStartMoney(int $userId): Money;
}
