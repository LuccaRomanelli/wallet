<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\Transfer\UserNotFoundException;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\WalletBalanceService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class GetUserBalanceController extends Controller
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private WalletBalanceService $walletBalanceService
    ) {}

    public function __invoke(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if ($user === null) {
            throw new UserNotFoundException('id');
        }

        $balance = $this->walletBalanceService->getBalance($id);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'document' => (string) $user->document,
                'user_type' => $user->user_type->value,
                'balance' => $balance->toDecimal(),
            ],
        ], Response::HTTP_OK);
    }
}
