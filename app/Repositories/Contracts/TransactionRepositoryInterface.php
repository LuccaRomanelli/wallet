<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\ValueObjects\Money\Money;

interface TransactionRepositoryInterface
{
    public function sumCompletedReceivedByUser(int $userId): Money;

    public function sumCompletedSentByUser(int $userId): Money;
}
