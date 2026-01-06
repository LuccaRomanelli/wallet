<?php

use App\Enums\UserType;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\UserService;
use App\ValueObjects\Money\Money;

beforeEach(function () {
    $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
    $this->service = new UserService($this->userRepository);
});

afterEach(function () {
    Mockery::close();
});

test('createUser calls repository with common user type', function () {
    $expectedUser = Mockery::mock(User::class);

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

    $user = $this->service->createUser(
        name: 'John Doe',
        email: 'john@example.com',
        password: 'password123',
        document: '52998224725',
        startMoney: Money::fromDecimal(100)
    );

    expect($user)->toBe($expectedUser);
});

test('createStore calls repository with merchant user type', function () {
    $expectedStore = Mockery::mock(User::class);

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

    $store = $this->service->createStore(
        name: 'My Store',
        email: 'store@example.com',
        password: 'password123',
        document: '11222333000181',
        startMoney: Money::fromDecimal(500)
    );

    expect($store)->toBe($expectedStore);
});

test('createUser passes correct start money to repository', function () {
    $expectedUser = Mockery::mock(User::class);

    $this->userRepository->shouldReceive('create')
        ->once()
        ->withArgs(function ($name, $email, $password, $document, $userType, $startMoney) {
            return $startMoney->getCents() === 10000;
        })
        ->andReturn($expectedUser);

    $this->service->createUser(
        name: 'John Doe',
        email: 'john@example.com',
        password: 'password123',
        document: '52998224725',
        startMoney: Money::fromDecimal(100)
    );
});

test('createStore passes correct start money to repository', function () {
    $expectedStore = Mockery::mock(User::class);

    $this->userRepository->shouldReceive('create')
        ->once()
        ->withArgs(function ($name, $email, $password, $document, $userType, $startMoney) {
            return $startMoney->getCents() === 50000;
        })
        ->andReturn($expectedStore);

    $this->service->createStore(
        name: 'My Store',
        email: 'store@example.com',
        password: 'password123',
        document: '11222333000181',
        startMoney: Money::fromDecimal(500)
    );
});

test('createUser passes zero start money when provided', function () {
    $expectedUser = Mockery::mock(User::class);

    $this->userRepository->shouldReceive('create')
        ->once()
        ->withArgs(function ($name, $email, $password, $document, $userType, $startMoney) {
            return $startMoney->getCents() === 0;
        })
        ->andReturn($expectedUser);

    $this->service->createUser(
        name: 'John Doe',
        email: 'john@example.com',
        password: 'password123',
        document: '52998224725',
        startMoney: Money::zero()
    );
});

test('createStore passes zero start money when provided', function () {
    $expectedStore = Mockery::mock(User::class);

    $this->userRepository->shouldReceive('create')
        ->once()
        ->withArgs(function ($name, $email, $password, $document, $userType, $startMoney) {
            return $startMoney->getCents() === 0;
        })
        ->andReturn($expectedStore);

    $this->service->createStore(
        name: 'My Store',
        email: 'store@example.com',
        password: 'password123',
        document: '11222333000181',
        startMoney: Money::zero()
    );
});
