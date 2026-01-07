<?php

namespace Database\Factories;

use App\Enums\AuditFailureCode;
use App\Enums\AuditStatus;
use App\Models\TransactionAuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TransactionAuditLog>
 */
class TransactionAuditLogFactory extends Factory
{
    protected $model = TransactionAuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payer_id' => null,
            'payee_id' => null,
            'amount' => fake()->numberBetween(1000, 100000),
            'status' => AuditStatus::Pending,
            'failure_reason' => null,
            'failure_code' => null,
            'client_ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'request_id' => Str::uuid()->toString(),
            'authorization_response' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AuditStatus::Pending,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AuditStatus::Completed,
        ]);
    }

    public function failed(AuditFailureCode $code, string $reason): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AuditStatus::Failed,
            'failure_code' => $code,
            'failure_reason' => $reason,
        ]);
    }
}
