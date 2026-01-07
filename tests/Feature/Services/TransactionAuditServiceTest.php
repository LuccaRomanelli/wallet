<?php

use App\Enums\AuditFailureCode;
use App\Enums\AuditStatus;
use App\Models\TransactionAuditLog;
use App\Services\TransactionAuditService;
use App\ValueObjects\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('creates pending audit log with correct data', function () {
    $service = app(TransactionAuditService::class);

    $auditLog = $service->createPendingLog(1, 2, new Money(10000));

    expect($auditLog)
        ->toBeInstanceOf(TransactionAuditLog::class)
        ->payer_id->toBe(1)
        ->payee_id->toBe(2)
        ->amount->getCents()->toBe(10000)
        ->status->toBe(AuditStatus::Pending)
        ->request_id->toBeString()
        ->client_ip->toBeString();
});

test('creates pending audit log with nullable payer and payee', function () {
    $service = app(TransactionAuditService::class);

    $auditLog = $service->createPendingLog(null, null, new Money(5000));

    expect($auditLog)
        ->payer_id->toBeNull()
        ->payee_id->toBeNull()
        ->amount->getCents()->toBe(5000)
        ->status->toBe(AuditStatus::Pending);
});

test('marks audit log as completed', function () {
    $service = app(TransactionAuditService::class);

    $auditLog = $service->createPendingLog(1, 2, new Money(10000));
    $authResponse = ['status' => 'success', 'data' => ['authorization' => true]];

    $updatedLog = $service->markAsCompleted($auditLog, $authResponse);

    expect($updatedLog)
        ->status->toBe(AuditStatus::Completed)
        ->authorization_response->toBe($authResponse);
});

test('marks audit log as completed without authorization response', function () {
    $service = app(TransactionAuditService::class);

    $auditLog = $service->createPendingLog(1, 2, new Money(10000));

    $updatedLog = $service->markAsCompleted($auditLog);

    expect($updatedLog)
        ->status->toBe(AuditStatus::Completed)
        ->authorization_response->toBeNull();
});

test('marks audit log as failed with reason and code', function () {
    $service = app(TransactionAuditService::class);

    $auditLog = $service->createPendingLog(1, 2, new Money(10000));

    $updatedLog = $service->markAsFailed(
        $auditLog,
        'Insufficient balance to complete the transfer.',
        AuditFailureCode::InsufficientBalance
    );

    expect($updatedLog)
        ->status->toBe(AuditStatus::Failed)
        ->failure_reason->toBe('Insufficient balance to complete the transfer.')
        ->failure_code->toBe(AuditFailureCode::InsufficientBalance);
});

test('generates unique request ids', function () {
    $service = app(TransactionAuditService::class);

    $auditLog1 = $service->createPendingLog(1, 2, new Money(10000));
    $auditLog2 = $service->createPendingLog(1, 2, new Money(10000));

    expect($auditLog1->request_id)->not->toBe($auditLog2->request_id);
});
