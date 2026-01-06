<?php

use App\Exceptions\InvalidValueObjectConversion\Identification\InvalidCPF;
use App\ValueObjects\Identification\CPF;

test('should create a valid CPF object with a valid CPF number', function () {
    // Valid CPF number
    $validCPF = '52998224725';

    $cpf = new CPF($validCPF);

    expect($cpf->getValue())->toBe($validCPF)
        ->and((string) $cpf)->toBe($validCPF);
});

test('should create a valid CPF object with a formatted CPF number', function () {
    // Valid CPF number with formatting
    $formattedCPF = '529.982.247-25';
    $cleanCPF     = '52998224725';

    $cpf = new CPF($formattedCPF);

    expect($cpf->getValue())->toBe($cleanCPF)
        ->and((string) $cpf)->toBe($cleanCPF);
});

test('should throw an exception when CPF has all the same digits', function () {
    // CPF with all the same digits
    $invalidCPF = '11111111111';

    expect(fn () => new CPF($invalidCPF))->toThrow(InvalidCPF::class);
});

test('should throw an exception when CPF has wrong length', function () {
    // CPF with wrong length - note that short CPFs are padded with zeros in normalizeCpf
    // so we need to use a value that will be invalid after normalization
    $longCPF = '123456789012';

    expect(fn () => new CPF($longCPF))->toThrow(InvalidCPF::class);
});

test('should throw an exception when CPF has invalid verification digits', function () {
    // CPF with invalid verification digits
    $invalidCPF = '52998224726'; // Changed the last digit

    expect(fn () => new CPF($invalidCPF))->toThrow(InvalidCPF::class);
});

test('should validate a CPF correctly', function () {
    $validCPF = '52998224725';
    $cpf      = new CPF($validCPF);

    // Use reflection to access a private validate method
    $reflectionClass = new ReflectionClass(CPF::class);
    $method          = $reflectionClass->getMethod('validate');
    $method->setAccessible(true);

    // This should not throw an exception
    $method->invoke($cpf, $validCPF);

    // This assertion is just to make sure the test passes
    expect(true)->toBeTrue();
});

test('checkDigitIsValid method works correctly', function () {
    $validCPF = '52998224725';
    $cpf      = new CPF($validCPF);

    $reflectionClass = new ReflectionClass(CPF::class);
    $method          = $reflectionClass->getMethod('checkDigitIsValid');
    $method->setAccessible(true);

    expect($method->invoke($cpf, 9, $validCPF))->toBeTrue()
        ->and($method->invoke($cpf, 10, $validCPF))->toBeTrue();


    $invalidCPF = '52998224726'; // Changed the last digit
    expect($method->invoke($cpf, 10, $invalidCPF))->toBeFalse();
});
