<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditFailureCode;
use App\Enums\AuditStatus;
use App\Models\TransactionAuditLog;
use App\Services\Contracts\TransactionAuditServiceInterface;
use App\ValueObjects\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TransactionAuditService implements TransactionAuditServiceInterface
{
    public function __construct(
        private Request $request
    ) {}

    public function createPendingLog(
        ?int $payerId,
        ?int $payeeId,
        Money $amount
    ): TransactionAuditLog {
        return TransactionAuditLog::create([
            'payer_id' => $payerId,
            'payee_id' => $payeeId,
            'amount' => $amount->getCents(),
            'status' => AuditStatus::Pending,
            'client_ip' => $this->request->ip() ?? '0.0.0.0',
            'user_agent' => $this->request->userAgent(),
            'request_id' => Str::uuid()->toString(),
        ]);
    }

    public function markAsCompleted(
        TransactionAuditLog $auditLog,
        ?array $authorizationResponse = null
    ): TransactionAuditLog {
        $auditLog->update([
            'status' => AuditStatus::Completed,
            'authorization_response' => $authorizationResponse,
        ]);

        return $auditLog;
    }

    public function markAsFailed(
        TransactionAuditLog $auditLog,
        string $failureReason,
        AuditFailureCode $failureCode
    ): TransactionAuditLog {
        $auditLog->update([
            'status' => AuditStatus::Failed,
            'failure_reason' => $failureReason,
            'failure_code' => $failureCode,
        ]);

        return $auditLog;
    }
}
