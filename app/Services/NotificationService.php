<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Transaction;
use App\Services\Contracts\NotificationServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService implements NotificationServiceInterface
{
    public function sendTransferNotification(Transaction $transaction): bool
    {
        try {
            $response = Http::timeout(config('services.external_notification.timeout'))
                ->post(config('services.external_notification.url'), [
                    'email' => $transaction->payee->email,
                    'message' => sprintf(
                        'You received a transfer of R$ %s from %s',
                        $transaction->amount->toDecimal(),
                        $transaction->payer->name
                    ),
                ]);

            if ($response->successful()) {
                Log::info('Transfer notification sent successfully', [
                    'transaction_id' => $transaction->id,
                    'payee_id' => $transaction->payee_id,
                ]);

                return true;
            }

            Log::warning('Notification service returned non-success', [
                'transaction_id' => $transaction->id,
                'status' => $response->status(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to send transfer notification', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
