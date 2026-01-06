<?php

use App\Enums\UserType;
use App\Exceptions\Transfer\InsufficientBalanceException;
use App\Exceptions\Transfer\MerchantCannotTransferException;
use App\Exceptions\Transfer\SelfTransferException;
use App\Exceptions\Transfer\TransferAuthorizationException;
use App\Exceptions\Transfer\UserNotFoundException;
use App\Jobs\SendTransferNotificationJob;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\AuthorizationServiceInterface;
use App\Services\TransferService;
use App\Services\WalletBalanceService;
use App\ValueObjects\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();

    $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
    $this->walletBalanceService = Mockery::mock(WalletBalanceService::class);
    $this->authorizationService = Mockery::mock(AuthorizationServiceInterface::class);

    $this->service = new TransferService(
        $this->userRepository,
        $this->walletBalanceService,
        $this->authorizationService
    );
});

afterEach(function () {
    Mockery::close();
});

test('throws UserNotFoundException when payer not found', function () {
    $this->userRepository->shouldReceive('find')
        ->with(1)
        ->andReturn(null);

    $this->service->transfer(1, 2, new Money(10000));
})->throws(UserNotFoundException::class, 'User not found: payer');

test('throws UserNotFoundException when payee not found', function () {
    $payer = User::factory()->common()->make(['id' => 1]);

    $this->userRepository->shouldReceive('find')
        ->with(1)
        ->andReturn($payer);

    $this->userRepository->shouldReceive('find')
        ->with(2)
        ->andReturn(null);

    $this->service->transfer(1, 2, new Money(10000));
})->throws(UserNotFoundException::class, 'User not found: payee');

test('throws SelfTransferException when payer and payee are the same', function () {
    $user = User::factory()->common()->make(['id' => 1]);

    $this->userRepository->shouldReceive('find')
        ->with(1)
        ->andReturn($user);

    $this->service->transfer(1, 1, new Money(10000));
})->throws(SelfTransferException::class, 'Cannot transfer to yourself.');

test('throws MerchantCannotTransferException when payer is merchant', function () {
    $payer = User::factory()->merchant()->make(['id' => 1]);
    $payee = User::factory()->common()->make(['id' => 2]);

    $this->userRepository->shouldReceive('find')
        ->with(1)
        ->andReturn($payer);

    $this->userRepository->shouldReceive('find')
        ->with(2)
        ->andReturn($payee);

    $this->service->transfer(1, 2, new Money(10000));
})->throws(MerchantCannotTransferException::class, 'Merchants are not allowed to send transfers.');

test('throws InsufficientBalanceException when balance is not enough', function () {
    $payer = User::factory()->common()->make(['id' => 1]);
    $payee = User::factory()->common()->make(['id' => 2]);

    $this->userRepository->shouldReceive('find')
        ->with(1)
        ->andReturn($payer);

    $this->userRepository->shouldReceive('find')
        ->with(2)
        ->andReturn($payee);

    $this->walletBalanceService->shouldReceive('hasSufficientBalance')
        ->with(1, Mockery::type(Money::class))
        ->andReturn(false);

    $this->service->transfer(1, 2, new Money(10000));
})->throws(InsufficientBalanceException::class, 'Insufficient balance to complete the transfer.');

test('throws TransferAuthorizationException when authorization fails', function () {
    $payer = User::factory()->common()->create(['start_money' => 100000]);
    $payee = User::factory()->common()->create(['start_money' => 0]);

    $userRepository = app(UserRepositoryInterface::class);
    $walletBalanceService = app(WalletBalanceService::class);

    $authorizationService = Mockery::mock(AuthorizationServiceInterface::class);
    $authorizationService->shouldReceive('authorize')
        ->andThrow(new TransferAuthorizationException('Transfer not authorized.'));

    $service = new TransferService(
        $userRepository,
        $walletBalanceService,
        $authorizationService
    );

    $service->transfer($payer->id, $payee->id, new Money(10000));
})->throws(TransferAuthorizationException::class);

test('successfully creates transaction when all validations pass', function () {
    $payer = User::factory()->common()->create(['start_money' => 100000]);
    $payee = User::factory()->common()->create(['start_money' => 0]);

    $userRepository = app(UserRepositoryInterface::class);
    $walletBalanceService = app(WalletBalanceService::class);

    $authorizationService = Mockery::mock(AuthorizationServiceInterface::class);
    $authorizationService->shouldReceive('authorize')
        ->andReturn(['status' => 'success', 'data' => ['authorization' => true]]);

    $service = new TransferService(
        $userRepository,
        $walletBalanceService,
        $authorizationService
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

    $authorizationService = Mockery::mock(AuthorizationServiceInterface::class);
    $authorizationService->shouldReceive('authorize')
        ->andReturn(['status' => 'success', 'data' => ['authorization' => true]]);

    $service = new TransferService(
        $userRepository,
        $walletBalanceService,
        $authorizationService
    );

    $service->transfer($payer->id, $payee->id, new Money(10000));

    Queue::assertPushed(SendTransferNotificationJob::class);
});
