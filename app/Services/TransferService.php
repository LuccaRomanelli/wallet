<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\UserType;
use App\Exceptions\Transfer\InsufficientBalanceException;
use App\Exceptions\Transfer\MerchantCannotTransferException;
use App\Exceptions\Transfer\SelfTransferException;
use App\Exceptions\Transfer\UserNotFoundException;
use App\Jobs\SendTransferNotificationJob;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\AuthorizationServiceInterface;
use App\ValueObjects\Money\Money;
use Illuminate\Support\Facades\DB;

class TransferService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private WalletBalanceService $walletBalanceService,
        private AuthorizationServiceInterface $authorizationService,
    ) {}

    /**
     * Execute a transfer between two users.
     *
     * @throws UserNotFoundException
     * @throws SelfTransferException
     * @throws MerchantCannotTransferException
     * @throws InsufficientBalanceException
     * @throws \App\Exceptions\Transfer\TransferAuthorizationException
     */
    public function transfer(int $payerId, int $payeeId, Money $amount): Transaction
    {
        $payer = $this->userRepository->find($payerId);
        if (! $payer) {
            throw new UserNotFoundException('payer');
        }

        $payee = $this->userRepository->find($payeeId);
        if (! $payee) {
            throw new UserNotFoundException('payee');
        }

        $this->validateTransfer($payer, $payee, $amount);

        $authorizationResponse = $this->authorizationService->authorize();

        $transaction = $this->executeTransfer(
            $payer,
            $payee,
            $amount,
            $authorizationResponse
        );

        SendTransferNotificationJob::dispatch($transaction);

        return $transaction;
    }

    private function validateTransfer(User $payer, User $payee, Money $amount): void
    {
        if ($payer->id === $payee->id) {
            throw new SelfTransferException;
        }

        if ($payer->user_type === UserType::Merchant) {
            throw new MerchantCannotTransferException;
        }

        if (! $this->walletBalanceService->hasSufficientBalance($payer->id, $amount)) {
            throw new InsufficientBalanceException;
        }
    }

    /**
     * @param  array<string, mixed>  $authorizationResponse
     */
    private function executeTransfer(
        User $payer,
        User $payee,
        Money $amount,
        array $authorizationResponse
    ): Transaction {
        return DB::transaction(function () use ($payer, $payee, $amount, $authorizationResponse) {
            User::where('id', $payer->id)->lockForUpdate()->first();

            if (! $this->walletBalanceService->hasSufficientBalance($payer->id, $amount)) {
                throw new InsufficientBalanceException;
            }

            return Transaction::create([
                'payer_id' => $payer->id,
                'payee_id' => $payee->id,
                'amount' => $amount->getCents(),
                'status' => TransactionStatus::Completed,
                'authorization_response' => $authorizationResponse,
            ]);
        });
    }
}
