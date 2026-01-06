<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Services\UserService;
use App\ValueObjects\Money\Money;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class StoreUserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function __invoke(StoreUserRequest $request): JsonResponse
    {
        $startMoney = Money::fromDecimal($request->validated('start_money', 0));

        $user = $this->userService->createUser(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
            document: $request->validated('document'),
            startMoney: $startMoney
        );

        return response()->json([
            'message' => 'User created successfully.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], Response::HTTP_CREATED);
    }
}
