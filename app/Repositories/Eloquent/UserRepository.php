<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\UserType;
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

    public function create(
        string $name,
        string $email,
        string $password,
        string $document,
        UserType $userType,
        Money $startMoney
    ): User {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'document' => $document,
            'user_type' => $userType,
            'start_money' => $startMoney->getCents(),
        ]);
    }
}
