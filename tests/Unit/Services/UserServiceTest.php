<?php

use App\DTOs\UserDTO;
use App\Enums\UserType;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\UserService;
use App\ValueObjects\Identification\CPF;
use App\ValueObjects\Money\Money;

beforeEach(function () {
    $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
    $this->service = new UserService($this->userRepository);
});

afterEach(function () {
    Mockery::close();
});

test('createAccount calls repository with common user type', function () {
    $expectedUser = new UserDTO(
        id: 1,
        name: 'John Doe',
        email: 'john@example.com',
        document: new CPF('52998224725'),
        userType: UserType::Common,
        startMoney: Money::fromDecimal(100)
    );

    $this->userRepository->shouldReceive('create')
        ->once()
        ->with(
            'John Doe',
            'john@example.com',
            'password123',
            '52998224725',
            UserType::Common,
            Mockery::type(Money::class)
        )
        ->andReturn($expectedUser);

    $user = $this->service->createAccount(
        name: 'John Doe',
        email: 'john@example.com',
        password: 'password123',
        document: '52998224725',
        userType: UserType::Common,
        startMoney: Money::fromDecimal(100)
    );

    expect($user)->toBe($expectedUser);
});

test('createAccount calls repository with merchant user type', function () {
    $expectedStore = new UserDTO(
        id: 1,
        name: 'My Store',
        email: 'store@example.com',
        document: new CPF('52998224725'),
        userType: UserType::Merchant,
        startMoney: Money::fromDecimal(500)
    );

    $this->userRepository->shouldReceive('create')
        ->once()
        ->with(
            'My Store',
            'store@example.com',
            'password123',
            '11222333000181',
            UserType::Merchant,
            Mockery::type(Money::class)
        )
        ->andReturn($expectedStore);

    $store = $this->service->createAccount(
        name: 'My Store',
        email: 'store@example.com',
        password: 'password123',
        document: '11222333000181',
        userType: UserType::Merchant,
        startMoney: Money::fromDecimal(500)
    );

    expect($store)->toBe($expectedStore);
});

test('createAccount passes correct start money to repository', function () {
    $expectedUser = new UserDTO(
        id: 1,
        name: 'John Doe',
        email: 'john@example.com',
        document: new CPF('52998224725'),
        userType: UserType::Common,
        startMoney: Money::fromDecimal(100)
    );

    $this->userRepository->shouldReceive('create')
        ->once()
        ->withArgs(function ($name, $email, $password, $document, $userType, $startMoney) {
            return $startMoney->getCents() === 10000;
        })
        ->andReturn($expectedUser);

    $this->service->createAccount(
        name: 'John Doe',
        email: 'john@example.com',
        password: 'password123',
        document: '52998224725',
        userType: UserType::Common,
        startMoney: Money::fromDecimal(100)
    );
});

test('createAccount passes zero start money when provided', function () {
    $expectedUser = new UserDTO(
        id: 1,
        name: 'John Doe',
        email: 'john@example.com',
        document: new CPF('52998224725'),
        userType: UserType::Common,
        startMoney: Money::zero()
    );

    $this->userRepository->shouldReceive('create')
        ->once()
        ->withArgs(function ($name, $email, $password, $document, $userType, $startMoney) {
            return $startMoney->getCents() === 0;
        })
        ->andReturn($expectedUser);

    $this->service->createAccount(
        name: 'John Doe',
        email: 'john@example.com',
        password: 'password123',
        document: '52998224725',
        userType: UserType::Common,
        startMoney: Money::zero()
    );
});
