<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\ValueObjects\Money\Money;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Concurrency;

class WalletBalanceService
{
    private const CACHE_TTL = 3600;
    private const CACHE_KEY_PREFIX = 'wallet_balance:user:';

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TransactionRepositoryInterface $transactionRepository
    ) {}

    public function getBalance(int $userId): Money
    {
        $cacheKey = $this->getCacheKey($userId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            return $this->calculateBalance($userId);
        });
    }

    public function calculateBalance(int $userId): Money
    {
        [$startMoney, $received, $sent] = Concurrency::run([
            fn () => $this->userRepository->getStartMoney($userId),
            fn () => $this->transactionRepository->sumCompletedReceivedByUser($userId),
            fn () => $this->transactionRepository->sumCompletedSentByUser($userId),
        ]);

        return $startMoney->add($received)->subtract($sent);
    }

    public function invalidateCacheForUsers(int ...$userIds): void
    {
        foreach ($userIds as $userId) {
            $this->invalidateCache($userId);
        }
    }

    public function hasSufficientBalance(int $userId, Money $amount): bool
    {
        $balance = $this->getBalance($userId);

        return $balance->isGreaterThanOrEqual($amount);
    }

    private function invalidateCache(int $userId): bool
    {
        return Cache::forget($this->getCacheKey($userId));
    }

    private function getCacheKey(int $userId): string
    {
        return self::CACHE_KEY_PREFIX . $userId;
    }
}
