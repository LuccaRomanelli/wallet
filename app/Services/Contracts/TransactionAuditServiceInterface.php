<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Enums\AuditFailureCode;
use App\Models\TransactionAuditLog;
use App\ValueObjects\Money\Money;

interface TransactionAuditServiceInterface
{
    /**
     * Create an audit log with pending status.
     */
    public function createPendingLog(
        ?int $payerId,
        ?int $payeeId,
        Money $amount
    ): TransactionAuditLog;

    /**
     * Mark the audit log as completed.
     *
     * @param  array<string, mixed>|null  $authorizationResponse
     */
    public function markAsCompleted(
        TransactionAuditLog $auditLog,
        ?array $authorizationResponse = null
    ): TransactionAuditLog;

    /**
     * Mark the audit log as failed.
     */
    public function markAsFailed(
        TransactionAuditLog $auditLog,
        string $failureReason,
        AuditFailureCode $failureCode
    ): TransactionAuditLog;
}
