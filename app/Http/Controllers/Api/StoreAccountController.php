<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountRequest;
use App\Services\UserService;
use App\ValueObjects\Money\Money;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class StoreAccountController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function __invoke(StoreAccountRequest $request): JsonResponse
    {
        $startMoney = Money::fromDecimal($request->validated('start_money', 0));
        $userType = UserType::from($request->validated('user_type'));

        $user = $this->userService->createAccount(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
            document: $request->validated('document'),
            userType: $userType,
            startMoney: $startMoney
        );

        return response()->json([
            'message' => 'Account created successfully.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'document' => (string) $user->document,
                'user_type' => $user->user_type->value,
            ],
        ], Response::HTTP_CREATED);
    }
}
