<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\ValueObjects\Money\Money;
use Illuminate\Support\Facades\Cache;

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
        $startMoney = $this->userRepository->getStartMoney($userId);
        $received = $this->transactionRepository->sumCompletedReceivedByUser($userId);
        $sent = $this->transactionRepository->sumCompletedSentByUser($userId);

        return $startMoney->add($received)->subtract($sent);
    }

    public function invalidateCache(int $userId): bool
    {
        return Cache::forget($this->getCacheKey($userId));
    }

    public function invalidateCacheForUsers(int ...$userIds): void
    {
        foreach ($userIds as $userId) {
            $this->invalidateCache($userId);
        }
    }

    public function getCacheKey(int $userId): string
    {
        return self::CACHE_KEY_PREFIX . $userId;
    }

    public function hasSufficientBalance(int $userId, Money $amount): bool
    {
        $balance = $this->getBalance($userId);

        return $balance->isGreaterThanOrEqual($amount);
    }

    public function refreshCache(int $userId): Money
    {
        $this->invalidateCache($userId);

        return $this->getBalance($userId);
    }
}
