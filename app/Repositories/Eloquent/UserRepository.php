<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\DTOs\UserDTO;
use App\Enums\UserType;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\ValueObjects\Money\Money;

class UserRepository implements UserRepositoryInterface
{
    public function find(int $id): ?UserDTO
    {
        $user = User::find($id);

        return $user ? $this->toDTO($user) : null;
    }

    public function findWithLock(int $id): ?UserDTO
    {
        $user = User::where('id', $id)->lockForUpdate()->first();

        return $user ? $this->toDTO($user) : null;
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
    ): UserDTO {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'document' => $document,
            'user_type' => $userType,
            'start_money' => $startMoney->getCents(),
        ]);

        return $this->toDTO($user);
    }

    private function toDTO(User $user): UserDTO
    {
        return new UserDTO(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            document: $user->document,
            userType: $user->user_type,
            startMoney: $user->start_money ?? Money::zero(),
        );
    }
}
