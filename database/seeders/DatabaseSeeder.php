<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\User;
use App\ValueObjects\Identification\CNPJ;
use App\ValueObjects\Identification\CPF;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        for ($i = 0; $i < 5; $i++) {
            User::factory()->create([
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'document' => CPF::generate()->getValue(),
                'user_type' => UserType::Common,
            ]);
        }

        for ($i = 0; $i < 3; $i++) {
            User::factory()->create([
                'name' => fake()->company(),
                'email' => fake()->unique()->companyEmail(),
                'document' => CNPJ::generate()->getValue(),
                'user_type' => UserType::Merchant,
            ]);
        }
    }
}
