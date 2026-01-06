<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Transaction;
use App\Services\WalletBalanceService;

class TransactionObserver
{
    public function __construct(
        private WalletBalanceService $walletBalanceService
    ) {}

    public function created(Transaction $transaction): void
    {
        $this->invalidateRelatedCaches($transaction);
    }

    public function updated(Transaction $transaction): void
    {
        if ($transaction->wasChanged('status')) {
            $this->invalidateRelatedCaches($transaction);
        }
    }

    private function invalidateRelatedCaches(Transaction $transaction): void
    {
        $this->walletBalanceService->invalidateCacheForUsers(
            $transaction->payer_id,
            $transaction->payee_id
        );
    }
}
