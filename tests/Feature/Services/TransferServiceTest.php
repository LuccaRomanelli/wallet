<?php

use App\DTOs\UserDTO;
use App\Exceptions\Transfer\InsufficientBalanceException;
use App\Exceptions\Transfer\MerchantCannotTransferException;
use App\Exceptions\Transfer\SelfTransferException;
use App\Exceptions\Transfer\TransferAuthorizationException;
use App\Exceptions\Transfer\UserNotFoundException;
use App\Jobs\SendTransferNotificationJob;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\AuthorizationServiceInterface;
use App\Services\Contracts\TransactionAuditServiceInterface;
use App\Services\TransferService;
use App\Services\WalletBalanceService;
use App\ValueObjects\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

function userToDTO(User $user): UserDTO
{
    return new UserDTO(
        id: $user->id,
        name: $user->name,
        email: $user->email,
        document: $user->document,
        userType: $user->user_type,
        startMoney: $user->start_money,
    );
}

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

afterEach(function () {
    Mockery::close();
});

test('throws UserNotFoundException when payer not found', function () {
    $userRepository = Mockery::mock(UserRepositoryInterface::class);
    $walletBalanceService = Mockery::mock(WalletBalanceService::class);
    $authorizationService = Mockery::mock(AuthorizationServiceInterface::class);
    $auditService = Mockery::mock(TransactionAuditServiceInterface::class);

    $userRepository->shouldReceive('find')
        ->with(1)
        ->andReturn(null);

    $userRepository->shouldReceive('find')
        ->with(2)
        ->andReturn(null);

    $auditService->shouldReceive('createPendingLog')->andReturn(new \App\Models\TransactionAuditLog);
    $auditService->shouldReceive('markAsFailed')->andReturnUsing(fn ($log) => $log);

    $service = new TransferService(
        $userRepository,
        $walletBalanceService,
        $authorizationService,
        $auditService
    );

    $service->transfer(1, 2, new Money(10000));
})->throws(UserNotFoundException::class, 'User not found: payer');

test('throws UserNotFoundException when payee not found', function () {
    $payer = User::factory()->common()->create(['id' => 1]);

    $userRepository = Mockery::mock(UserRepositoryInterface::class);
    $walletBalanceService = Mockery::mock(WalletBalanceService::class);
    $authorizationService = Mockery::mock(AuthorizationServiceInterface::class);
    $auditService = Mockery::mock(TransactionAuditServiceInterface::class);

    $userRepository->shouldReceive('find')
        ->with(1)
        ->andReturn(userToDTO($payer));

    $userRepository->shouldReceive('find')
        ->with(2)
        ->andReturn(null);

    $auditService->shouldReceive('createPendingLog')->andReturn(new \App\Models\TransactionAuditLog);
    $auditService->shouldReceive('markAsFailed')->andReturnUsing(fn ($log) => $log);

    $service = new TransferService(
        $userRepository,
        $walletBalanceService,
        $authorizationService,
        $auditService
    );

    $service->transfer(1, 2, new Money(10000));
})->throws(UserNotFoundException::class, 'User not found: payee');

test('throws SelfTransferException when payer and payee are the same', function () {
    $user = User::factory()->common()->create(['id' => 1]);

    $userRepository = Mockery::mock(UserRepositoryInterface::class);
    $walletBalanceService = Mockery::mock(WalletBalanceService::class);
    $authorizationService = Mockery::mock(AuthorizationServiceInterface::class);
    $auditService = Mockery::mock(TransactionAuditServiceInterface::class);

    $userRepository->shouldReceive('find')
        ->with(1)
        ->andReturn(userToDTO($user));

    $auditService->shouldReceive('createPendingLog')->andReturn(new \App\Models\TransactionAuditLog);
    $auditService->shouldReceive('markAsFailed')->andReturnUsing(fn ($log) => $log);

    $service = new TransferService(
        $userRepository,
        $walletBalanceService,
        $authorizationService,
        $auditService
    );

    $service->transfer(1, 1, new Money(10000));
})->throws(SelfTransferException::class, 'Cannot transfer to yourself.');

test('throws MerchantCannotTransferException when payer is merchant', function () {
    $payer = User::factory()->merchant()->create(['id' => 1]);
    $payee = User::factory()->common()->create(['id' => 2]);

    $userRepository = Mockery::mock(UserRepositoryInterface::class);
    $walletBalanceService = Mockery::mock(WalletBalanceService::class);
    $authorizationService = Mockery::mock(AuthorizationServiceInterface::class);
    $auditService = Mockery::mock(TransactionAuditServiceInterface::class);

    $userRepository->shouldReceive('find')
        ->with(1)
        ->andReturn(userToDTO($payer));

    $userRepository->shouldReceive('find')
        ->with(2)
        ->andReturn(userToDTO($payee));

    $auditService->shouldReceive('createPendingLog')->andReturn(new \App\Models\TransactionAuditLog);
    $auditService->shouldReceive('markAsFailed')->andReturnUsing(fn ($log) => $log);

    $service = new TransferService(
        $userRepository,
        $walletBalanceService,
        $authorizationService,
        $auditService
    );

    $service->transfer(1, 2, new Money(10000));
})->throws(MerchantCannotTransferException::class, 'Merchants are not allowed to send transfers.');

test('throws InsufficientBalanceException when balance is not enough', function () {
    $payer = User::factory()->common()->create(['id' => 1]);
    $payee = User::factory()->common()->create(['id' => 2]);

    $userRepository = Mockery::mock(UserRepositoryInterface::class);
    $walletBalanceService = Mockery::mock(WalletBalanceService::class);
    $authorizationService = Mockery::mock(AuthorizationServiceInterface::class);
    $auditService = Mockery::mock(TransactionAuditServiceInterface::class);

    $userRepository->shouldReceive('find')
        ->with(1)
        ->andReturn(userToDTO($payer));

    $userRepository->shouldReceive('find')
        ->with(2)
        ->andReturn(userToDTO($payee));

    $walletBalanceService->shouldReceive('hasSufficientBalance')
        ->with(1, Mockery::type(Money::class))
        ->andReturn(false);

    $auditService->shouldReceive('createPendingLog')->andReturn(new \App\Models\TransactionAuditLog);
    $auditService->shouldReceive('markAsFailed')->andReturnUsing(fn ($log) => $log);

    $service = new TransferService(
        $userRepository,
        $walletBalanceService,
        $authorizationService,
        $auditService
    );

    $service->transfer(1, 2, new Money(10000));
})->throws(InsufficientBalanceException::class, 'Insufficient balance to complete the transfer.');

test('throws TransferAuthorizationException when authorization fails', function () {
    $payer = User::factory()->common()->create(['start_money' => 100000]);
    $payee = User::factory()->common()->create(['start_money' => 0]);

    $userRepository = app(UserRepositoryInterface::class);
    $walletBalanceService = app(WalletBalanceService::class);
    $auditService = Mockery::mock(TransactionAuditServiceInterface::class);

    $authorizationService = Mockery::mock(AuthorizationServiceInterface::class);
    $authorizationService->shouldReceive('authorize')
        ->andThrow(new TransferAuthorizationException('Transfer not authorized.'));

    $auditService->shouldReceive('createPendingLog')->andReturn(new \App\Models\TransactionAuditLog);
    $auditService->shouldReceive('markAsFailed')->andReturnUsing(fn ($log) => $log);

    $service = new TransferService(
        $userRepository,
        $walletBalanceService,
        $authorizationService,
        $auditService
    );

    $service->transfer($payer->id, $payee->id, new Money(10000));
})->throws(TransferAuthorizationException::class);

test('successfully creates transaction when all validations pass', function () {
    $payer = User::factory()->common()->create(['start_money' => 100000]);
    $payee = User::factory()->common()->create(['start_money' => 0]);

    $userRepository = app(UserRepositoryInterface::class);
    $walletBalanceService = app(WalletBalanceService::class);
    $auditService = Mockery::mock(TransactionAuditServiceInterface::class);

    $authorizationService = Mockery::mock(AuthorizationServiceInterface::class);
    $authorizationService->shouldReceive('authorize')
        ->andReturn(['status' => 'success', 'data' => ['authorization' => true]]);

    $auditService->shouldReceive('createPendingLog')->andReturn(new \App\Models\TransactionAuditLog);
    $auditService->shouldReceive('markAsCompleted')->andReturnUsing(fn ($log) => $log);

    $service = new TransferService(
        $userRepository,
        $walletBalanceService,
        $authorizationService,
        $auditService
    );

    $transaction = $service->transfer($payer->id, $payee->id, new Money(50000));

    expect($transaction->payer_id)->toBe($payer->id)
        ->and($transaction->payee_id)->toBe($payee->id)
        ->and($transaction->amount->getCents())->toBe(50000)
        ->and($transaction->status->value)->toBe('completed');

    Queue::assertPushed(SendTransferNotificationJob::class, function ($job) use ($transaction) {
        return $job->transaction->id === $transaction->id;
    });
});

test('dispatches notification job after successful transfer', function () {
    $payer = User::factory()->common()->create(['start_money' => 100000]);
    $payee = User::factory()->common()->create(['start_money' => 0]);

    $userRepository = app(UserRepositoryInterface::class);
    $walletBalanceService = app(WalletBalanceService::class);
    $auditService = Mockery::mock(TransactionAuditServiceInterface::class);

    $authorizationService = Mockery::mock(AuthorizationServiceInterface::class);
    $authorizationService->shouldReceive('authorize')
        ->andReturn(['status' => 'success', 'data' => ['authorization' => true]]);

    $auditService->shouldReceive('createPendingLog')->andReturn(new \App\Models\TransactionAuditLog);
    $auditService->shouldReceive('markAsCompleted')->andReturnUsing(fn ($log) => $log);

    $service = new TransferService(
        $userRepository,
        $walletBalanceService,
        $authorizationService,
        $auditService
    );

    $service->transfer($payer->id, $payee->id, new Money(10000));

    Queue::assertPushed(SendTransferNotificationJob::class);
});
