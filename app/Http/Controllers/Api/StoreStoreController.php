<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStoreRequest;
use App\Services\UserService;
use App\ValueObjects\Money\Money;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class StoreStoreController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function __invoke(StoreStoreRequest $request): JsonResponse
    {
        $startMoney = Money::fromDecimal($request->validated('start_money', 0));

        $store = $this->userService->createStore(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
            document: $request->validated('document'),
            startMoney: $startMoney
        );

        return response()->json([
            'message' => 'Store created successfully.',
            'data' => [
                'id' => $store->id,
                'name' => $store->name,
                'email' => $store->email,
            ],
        ], Response::HTTP_CREATED);
    }
}
