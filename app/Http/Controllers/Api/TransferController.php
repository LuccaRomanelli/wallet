<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\Transfer\InsufficientBalanceException;
use App\Exceptions\Transfer\MerchantCannotTransferException;
use App\Exceptions\Transfer\SelfTransferException;
use App\Exceptions\Transfer\TransferAuthorizationException;
use App\Exceptions\Transfer\UserNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransferRequest;
use App\Http\Resources\TransactionResource;
use App\Services\TransferService;
use App\ValueObjects\Money\Money;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TransferController extends Controller
{
    public function __construct(
        private TransferService $transferService
    ) {}

    public function __invoke(TransferRequest $request): JsonResponse
    {
        try {
            $amount = Money::fromDecimal($request->validated('value'));

            $transaction = $this->transferService->transfer(
                payerId: (int) $request->validated('payer'),
                payeeId: (int) $request->validated('payee'),
                amount: $amount
            );

            return response()->json([
                'message' => 'Transfer completed successfully.',
                'data' => new TransactionResource($transaction),
            ], Response::HTTP_CREATED);
        } catch (UserNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        } catch (InsufficientBalanceException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (MerchantCannotTransferException|SelfTransferException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_FORBIDDEN);
        } catch (TransferAuthorizationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }
}
