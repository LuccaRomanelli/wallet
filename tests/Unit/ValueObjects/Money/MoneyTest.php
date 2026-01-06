<?php

use App\Exceptions\InvalidValueObjectConversion\Money\InvalidMoney;
use App\ValueObjects\Money\Money;

test('should create a valid Money object with cents', function () {
    $money = new Money(1000);

    expect($money->getCents())->toBe(1000)
        ->and($money->toDecimal())->toBe('10.00')
        ->and((string) $money)->toBe('10.00');
});

test('should create a zero Money object', function () {
    $money = Money::zero();

    expect($money->getCents())->toBe(0)
        ->and($money->toDecimal())->toBe('0.00');
});

test('should create Money from decimal string', function () {
    $money = Money::fromDecimal('10.50');

    expect($money->getCents())->toBe(1050)
        ->and($money->toDecimal())->toBe('10.50');
});

test('should create Money from decimal float', function () {
    $money = Money::fromDecimal(10.50);

    expect($money->getCents())->toBe(1050);
});

test('should throw exception for negative cents', function () {
    expect(fn () => new Money(-100))->toThrow(InvalidMoney::class);
});

test('should add two Money objects correctly', function () {
    $money1 = new Money(1000);
    $money2 = new Money(500);

    $result = $money1->add($money2);

    expect($result->getCents())->toBe(1500)
        ->and($result->toDecimal())->toBe('15.00');
});

test('should subtract two Money objects correctly', function () {
    $money1 = new Money(1000);
    $money2 = new Money(300);

    $result = $money1->subtract($money2);

    expect($result->getCents())->toBe(700)
        ->and($result->toDecimal())->toBe('7.00');
});

test('isGreaterThanOrEqual should return true when greater', function () {
    $money1 = new Money(1000);
    $money2 = new Money(500);

    expect($money1->isGreaterThanOrEqual($money2))->toBeTrue();
});

test('isGreaterThanOrEqual should return true when equal', function () {
    $money1 = new Money(1000);
    $money2 = new Money(1000);

    expect($money1->isGreaterThanOrEqual($money2))->toBeTrue();
});

test('isGreaterThanOrEqual should return false when less', function () {
    $money1 = new Money(500);
    $money2 = new Money(1000);

    expect($money1->isGreaterThanOrEqual($money2))->toBeFalse();
});

test('should handle decimal conversion with rounding', function () {
    $money = Money::fromDecimal('10.555');

    expect($money->getCents())->toBe(1056);
});

test('should strip non-numeric characters from decimal string', function () {
    $money = Money::fromDecimal('$10.50');

    expect($money->getCents())->toBe(1050);
});
