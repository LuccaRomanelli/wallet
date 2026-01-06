<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Transaction;

interface NotificationServiceInterface
{
    /**
     * Send transfer notification to the payee.
     */
    public function sendTransferNotification(Transaction $transaction): bool;
}
