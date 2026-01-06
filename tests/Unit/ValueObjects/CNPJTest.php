<?php

use App\Exceptions\InvalidValueObjectConversion\Identification\InvalidCNPJ;
use App\ValueObjects\Identification\CNPJ;

test('should create a valid CNPJ object with a valid CNPJ number', function () {
    // Valid CNPJ number
    $validCNPJ = '11222333000181';

    $cnpj = new CNPJ($validCNPJ);

    expect($cnpj->getValue())->toBe($validCNPJ)
        ->and((string) $cnpj)->toBe($validCNPJ);
});

test('should create a valid CNPJ object with a formatted CNPJ number', function () {
    // Valid CNPJ number with formatting
    $formattedCNPJ = '11.222.333/0001-81';
    $cleanCNPJ     = '11222333000181';

    $cnpj = new CNPJ($formattedCNPJ);

    expect($cnpj->getValue())->toBe($cleanCNPJ)
        ->and((string) $cnpj)->toBe($cleanCNPJ);
});

test('should throw an exception when CNPJ has all the same digits', function () {
    // CNPJ with all the same digits
    $invalidCNPJ = '11111111111111';

    expect(fn () => new CNPJ($invalidCNPJ))->toThrow(InvalidCNPJ::class);
});

test('should throw an exception when CNPJ has wrong length', function () {
    // CNPJ with wrong length - note that short CNPJs are padded with zeros in normalizeCnpj
    // so we need to use a value that will be invalid after normalization
    $longCNPJ = '112223330001819';

    expect(fn () => new CNPJ($longCNPJ))->toThrow(InvalidCNPJ::class);
});

test('should throw an exception when CNPJ has invalid verification digits', function () {
    // CNPJ with invalid verification digits
    $invalidCNPJ = '11222333000182'; // Changed the last digit

    expect(fn () => new CNPJ($invalidCNPJ))->toThrow(InvalidCNPJ::class);
});

test('should validate a CNPJ correctly', function () {
    $validCNPJ = '11222333000181';
    $cnpj      = new CNPJ($validCNPJ);

    // Use reflection to access a private validate method
    $reflectionClass = new ReflectionClass(CNPJ::class);
    $method          = $reflectionClass->getMethod('validate');
    $method->setAccessible(true);

    // This should not throw an exception
    $method->invoke($cnpj, $validCNPJ);

    // This assertion is just to make sure the test passes
    expect(true)->toBeTrue();
});

test('checkDigitIsValid method works correctly', function () {
    $validCNPJ = '11222333000181';
    $cnpj      = new CNPJ($validCNPJ);

    $reflectionClass = new ReflectionClass(CNPJ::class);
    $method          = $reflectionClass->getMethod('checkDigitIsValid');
    $method->setAccessible(true);

    expect($method->invoke($cnpj, 12, $validCNPJ))->toBeTrue()
        ->and($method->invoke($cnpj, 13, $validCNPJ))->toBeTrue();


    $invalidCNPJ = '11222333000182'; // Changed the last digit
    expect($method->invoke($cnpj, 13, $invalidCNPJ))->toBeFalse();
});
