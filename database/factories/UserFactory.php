<?php

namespace Database\Factories;

use App\Enums\UserType;
use App\ValueObjects\Identification\CNPJ;
use App\ValueObjects\Identification\CPF;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'document' => CPF::generate()->getValue(),
            'user_type' => UserType::Common,
            'start_money' => 0,
        ];
    }

    public function common(): static
    {
        return $this->state(fn (array $attributes) => [
            'document' => CPF::generate()->getValue(),
            'user_type' => UserType::Common,
        ]);
    }

    public function merchant(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->company(),
            'document' => CNPJ::generate()->getValue(),
            'user_type' => UserType::Merchant,
        ]);
    }
}
