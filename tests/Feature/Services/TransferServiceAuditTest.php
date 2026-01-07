<?php

use App\Enums\AuditFailureCode;
use App\Enums\AuditStatus;
use App\Exceptions\Transfer\TransferAuthorizationException;
use App\Models\TransactionAuditLog;
use App\Models\User;
use App\Services\Contracts\AuthorizationServiceInterface;
use App\Services\TransferService;
use App\ValueObjects\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

afterEach(function () {
    Mockery::close();
});

test('creates audit log with completed status on successful transfer', function () {
    $payer = User::factory()->common()->create(['start_money' => 100000]);
    $payee = User::factory()->common()->create(['start_money' => 0]);

    $authService = Mockery::mock(AuthorizationServiceInterface::class);
    $authService->shouldReceive('authorize')
        ->andReturn(['status' => 'success', 'data' => ['authorization' => true]]);
    $this->app->instance(AuthorizationServiceInterface::class, $authService);

    $service = app(TransferService::class);
    $service->transfer($payer->id, $payee->id, new Money(10000));

    $auditLog = TransactionAuditLog::first();

    expect($auditLog)
        ->status->toBe(AuditStatus::Completed)
        ->payer_id->toBe($payer->id)
        ->payee_id->toBe($payee->id)
        ->amount->getCents()->toBe(10000)
        ->authorization_response->toBe(['status' => 'success', 'data' => ['authorization' => true]]);
});

test('creates audit log with failed status on insufficient balance', function () {
    $payer = User::factory()->common()->create(['start_money' => 1000]);
    $payee = User::factory()->common()->create(['start_money' => 0]);

    $service = app(TransferService::class);

    try {
        $service->transfer($payer->id, $payee->id, new Money(100000));
    } catch (\Exception $e) {
        // Expected exception
    }

    $auditLog = TransactionAuditLog::first();

    expect($auditLog)
        ->status->toBe(AuditStatus::Failed)
        ->failure_code->toBe(AuditFailureCode::InsufficientBalance)
        ->failure_reason->toBe('Insufficient balance to complete the transfer.');
});

test('creates audit log with failed status on merchant transfer', function () {
    $payer = User::factory()->merchant()->create(['start_money' => 100000]);
    $payee = User::factory()->common()->create(['start_money' => 0]);

    $service = app(TransferService::class);

    try {
        $service->transfer($payer->id, $payee->id, new Money(10000));
    } catch (\Exception $e) {
        // Expected exception
    }

    $auditLog = TransactionAuditLog::first();

    expect($auditLog)
        ->status->toBe(AuditStatus::Failed)
        ->failure_code->toBe(AuditFailureCode::MerchantCannotTransfer)
        ->failure_reason->toBe('Merchants are not allowed to send transfers.');
});

test('creates audit log with failed status on self transfer', function () {
    $user = User::factory()->common()->create(['start_money' => 100000]);

    $service = app(TransferService::class);

    try {
        $service->transfer($user->id, $user->id, new Money(10000));
    } catch (\Exception $e) {
        // Expected exception
    }

    $auditLog = TransactionAuditLog::first();

    expect($auditLog)
        ->status->toBe(AuditStatus::Failed)
        ->failure_code->toBe(AuditFailureCode::SelfTransfer)
        ->failure_reason->toBe('Cannot transfer to yourself.');
});

test('creates audit log with failed status on user not found', function () {
    $payer = User::factory()->common()->create(['start_money' => 100000]);

    $service = app(TransferService::class);

    try {
        $service->transfer($payer->id, 99999, new Money(10000));
    } catch (\Exception $e) {
        // Expected exception
    }

    $auditLog = TransactionAuditLog::first();

    expect($auditLog)
        ->status->toBe(AuditStatus::Failed)
        ->failure_code->toBe(AuditFailureCode::UserNotFound)
        ->failure_reason->toBe('User not found: payee');
});

test('creates audit log with failed status on authorization denied', function () {
    $payer = User::factory()->common()->create(['start_money' => 100000]);
    $payee = User::factory()->common()->create(['start_money' => 0]);

    $authService = Mockery::mock(AuthorizationServiceInterface::class);
    $authService->shouldReceive('authorize')
        ->andThrow(new TransferAuthorizationException('Transfer not authorized.'));
    $this->app->instance(AuthorizationServiceInterface::class, $authService);

    $service = app(TransferService::class);

    try {
        $service->transfer($payer->id, $payee->id, new Money(10000));
    } catch (\Exception $e) {
        // Expected exception
    }

    $auditLog = TransactionAuditLog::first();

    expect($auditLog)
        ->status->toBe(AuditStatus::Failed)
        ->failure_code->toBe(AuditFailureCode::AuthorizationDenied)
        ->failure_reason->toBe('Transfer not authorized.');
});

test('creates audit log with failed status on authorization service unavailable', function () {
    $payer = User::factory()->common()->create(['start_money' => 100000]);
    $payee = User::factory()->common()->create(['start_money' => 0]);

    $authService = Mockery::mock(AuthorizationServiceInterface::class);
    $authService->shouldReceive('authorize')
        ->andThrow(new TransferAuthorizationException('Authorization service unavailable.'));
    $this->app->instance(AuthorizationServiceInterface::class, $authService);

    $service = app(TransferService::class);

    try {
        $service->transfer($payer->id, $payee->id, new Money(10000));
    } catch (\Exception $e) {
        // Expected exception
    }

    $auditLog = TransactionAuditLog::first();

    expect($auditLog)
        ->status->toBe(AuditStatus::Failed)
        ->failure_code->toBe(AuditFailureCode::AuthorizationServiceUnavailable)
        ->failure_reason->toBe('Authorization service unavailable.');
});

test('audit log captures request_id as uuid', function () {
    $payer = User::factory()->common()->create(['start_money' => 100000]);
    $payee = User::factory()->common()->create(['start_money' => 0]);

    $authService = Mockery::mock(AuthorizationServiceInterface::class);
    $authService->shouldReceive('authorize')
        ->andReturn(['status' => 'success', 'data' => ['authorization' => true]]);
    $this->app->instance(AuthorizationServiceInterface::class, $authService);

    $service = app(TransferService::class);
    $service->transfer($payer->id, $payee->id, new Money(10000));

    $auditLog = TransactionAuditLog::first();

    expect($auditLog->request_id)
        ->toBeString()
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

test('audit log captures client ip', function () {
    $payer = User::factory()->common()->create(['start_money' => 100000]);
    $payee = User::factory()->common()->create(['start_money' => 0]);

    $authService = Mockery::mock(AuthorizationServiceInterface::class);
    $authService->shouldReceive('authorize')
        ->andReturn(['status' => 'success', 'data' => ['authorization' => true]]);
    $this->app->instance(AuthorizationServiceInterface::class, $authService);

    $service = app(TransferService::class);
    $service->transfer($payer->id, $payee->id, new Money(10000));

    $auditLog = TransactionAuditLog::first();

    expect($auditLog->client_ip)->toBeString();
});
