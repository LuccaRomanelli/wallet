<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\Contracts\NotificationServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendTransferNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public Transaction $transaction
    ) {
        $this->onQueue('notifications');
    }

    public function handle(NotificationServiceInterface $notificationService): void
    {
        $notificationService->sendTransferNotification($this->transaction);
    }
}
