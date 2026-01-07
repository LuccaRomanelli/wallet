<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\UserDTO;
use App\Enums\AuditFailureCode;
use App\Enums\TransactionStatus;
use App\Enums\UserType;
use App\Exceptions\Transfer\InsufficientBalanceException;
use App\Exceptions\Transfer\MerchantCannotTransferException;
use App\Exceptions\Transfer\SelfTransferException;
use App\Exceptions\Transfer\TransferAuthorizationException;
use App\Exceptions\Transfer\UserNotFoundException;
use App\Jobs\SendTransferNotificationJob;
use App\Models\Transaction;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\AuthorizationServiceInterface;
use App\Services\Contracts\TransactionAuditServiceInterface;
use App\ValueObjects\Money\Money;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;

class TransferService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private WalletBalanceService $walletBalanceService,
        private AuthorizationServiceInterface $authorizationService,
        private TransactionAuditServiceInterface $auditService,
    ) {}

    /**
     * Execute a transfer between two users.
     *
     * @throws UserNotFoundException
     * @throws SelfTransferException
     * @throws MerchantCannotTransferException
     * @throws InsufficientBalanceException
     * @throws TransferAuthorizationException
     */
    public function transfer(int $payerId, int $payeeId, Money $amount): Transaction
    {
        $auditLog = $this->auditService->createPendingLog($payerId, $payeeId, $amount);

        try {
            [$payer, $payee] = Concurrency::run([
                fn () => $this->userRepository->find($payerId),
                fn () => $this->userRepository->find($payeeId),
            ]);

            if (! $payer) {
                throw new UserNotFoundException('payer');
            }

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

            $this->auditService->markAsCompleted($auditLog, $authorizationResponse);

            SendTransferNotificationJob::dispatch($transaction);

            return $transaction;
        } catch (UserNotFoundException $e) {
            $this->auditService->markAsFailed($auditLog, $e->getMessage(), AuditFailureCode::UserNotFound);
            throw $e;
        } catch (SelfTransferException $e) {
            $this->auditService->markAsFailed($auditLog, $e->getMessage(), AuditFailureCode::SelfTransfer);
            throw $e;
        } catch (MerchantCannotTransferException $e) {
            $this->auditService->markAsFailed($auditLog, $e->getMessage(), AuditFailureCode::MerchantCannotTransfer);
            throw $e;
        } catch (InsufficientBalanceException $e) {
            $this->auditService->markAsFailed($auditLog, $e->getMessage(), AuditFailureCode::InsufficientBalance);
            throw $e;
        } catch (TransferAuthorizationException $e) {
            $failureCode = str_contains($e->getMessage(), 'unavailable')
                ? AuditFailureCode::AuthorizationServiceUnavailable
                : AuditFailureCode::AuthorizationDenied;

            $this->auditService->markAsFailed($auditLog, $e->getMessage(), $failureCode);
            throw $e;
        }
    }

    private function validateTransfer(UserDTO $payer, UserDTO $payee, Money $amount): void
    {
        if ($payer->id === $payee->id) {
            throw new SelfTransferException;
        }

        if ($payer->userType === UserType::Merchant) {
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
        UserDTO $payer,
        UserDTO $payee,
        Money $amount,
        array $authorizationResponse
    ): Transaction {
        return DB::transaction(function () use ($payer, $payee, $amount, $authorizationResponse) {
            $this->userRepository->findWithLock($payer->id);

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
