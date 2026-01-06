<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\ValueObjects\Money\Money;

class UserRepository implements UserRepositoryInterface
{
    public function find(int $id): ?User
    {
        return User::find($id);
    }

    public function getStartMoney(int $userId): Money
    {
        $user = User::findOrFail($userId);

        return $user->start_money ?? Money::zero();
    }
}
