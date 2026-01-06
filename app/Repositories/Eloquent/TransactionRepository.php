<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\ValueObjects\Money\Money;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function sumCompletedReceivedByUser(int $userId): Money
    {
        $sum = Transaction::query()
            ->where('payee_id', $userId)
            ->where('status', TransactionStatus::Completed)
            ->sum('amount');

        return new Money((int) ($sum ?? 0));
    }

    public function sumCompletedSentByUser(int $userId): Money
    {
        $sum = Transaction::query()
            ->where('payer_id', $userId)
            ->where('status', TransactionStatus::Completed)
            ->sum('amount');

        return new Money((int) ($sum ?? 0));
    }
}
