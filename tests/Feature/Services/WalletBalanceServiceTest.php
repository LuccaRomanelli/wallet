<?php

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\Eloquent\TransactionRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Services\WalletBalanceService;
use App\ValueObjects\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->userRepository = new UserRepository();
    $this->transactionRepository = new TransactionRepository();
    $this->service = new WalletBalanceService(
        $this->userRepository,
        $this->transactionRepository
    );
});

test('calculates balance with only start_money', function () {
    $user = User::factory()->create(['start_money' => 100000]);

    $balance = $this->service->calculateBalance($user->id);

    expect($balance->getCents())->toBe(100000)
        ->and($balance->toDecimal())->toBe('1000.00');
});

test('calculates balance with received transactions', function () {
    $user = User::factory()->create(['start_money' => 10000]);
    $payer = User::factory()->create(['start_money' => 100000]);

    Transaction::factory()->create([
        'payer_id' => $payer->id,
        'payee_id' => $user->id,
        'amount' => 5000,
        'status' => TransactionStatus::Completed,
    ]);

    $balance = $this->service->calculateBalance($user->id);

    expect($balance->getCents())->toBe(15000);
});

test('calculates balance with sent transactions', function () {
    $user = User::factory()->create(['start_money' => 10000]);
    $payee = User::factory()->create(['start_money' => 0]);

    Transaction::factory()->create([
        'payer_id' => $user->id,
        'payee_id' => $payee->id,
        'amount' => 3000,
        'status' => TransactionStatus::Completed,
    ]);

    $balance = $this->service->calculateBalance($user->id);

    expect($balance->getCents())->toBe(7000);
});

test('only counts completed transactions', function () {
    $user = User::factory()->create(['start_money' => 10000]);
    $payer = User::factory()->create(['start_money' => 100000]);

    Transaction::factory()->create([
        'payer_id' => $payer->id,
        'payee_id' => $user->id,
        'amount' => 5000,
        'status' => TransactionStatus::Completed,
    ]);

    Transaction::factory()->create([
        'payer_id' => $payer->id,
        'payee_id' => $user->id,
        'amount' => 10000,
        'status' => TransactionStatus::Failed,
    ]);

    $balance = $this->service->calculateBalance($user->id);

    expect($balance->getCents())->toBe(15000);
});

test('getBalance caches the result', function () {
    $user = User::factory()->create(['start_money' => 10000]);

    Cache::shouldReceive('remember')
        ->once()
        ->withArgs(function ($key, $ttl, $callback) use ($user) {
            return $key === "wallet_balance:user:{$user->id}" && $ttl === 3600;
        })
        ->andReturn(new Money(10000));

    $this->service->getBalance($user->id);
});

test('invalidateCacheForUsers removes cached balance for multiple users', function () {
    $user1 = User::factory()->create(['start_money' => 10000]);
    $user2 = User::factory()->create(['start_money' => 20000]);

    Cache::shouldReceive('forget')
        ->once()
        ->with("wallet_balance:user:{$user1->id}")
        ->andReturn(true);

    Cache::shouldReceive('forget')
        ->once()
        ->with("wallet_balance:user:{$user2->id}")
        ->andReturn(true);

    $this->service->invalidateCacheForUsers($user1->id, $user2->id);
});

test('hasSufficientBalance returns true when balance is sufficient', function () {
    $user = User::factory()->create(['start_money' => 10000]);

    $result = $this->service->hasSufficientBalance($user->id, new Money(5000));

    expect($result)->toBeTrue();
});

test('hasSufficientBalance returns false when balance is insufficient', function () {
    $user = User::factory()->create(['start_money' => 10000]);

    $result = $this->service->hasSufficientBalance($user->id, new Money(15000));

    expect($result)->toBeFalse();
});
