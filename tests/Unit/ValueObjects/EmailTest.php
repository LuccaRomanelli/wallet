<?php

use App\Exceptions\InvalidValueObjectConversion\Identification\InvalidEmail;
use App\ValueObjects\Identification\Email;

test('should create a valid Email object with a valid email', function () {
    $validEmail = 'test@example.com';

    $email = new Email($validEmail);

    expect($email->getValue())->toBe($validEmail)
        ->and((string) $email)->toBe($validEmail);
});

test('should normalize email to lowercase', function () {
    $mixedCaseEmail = 'Test@Example.COM';
    $normalizedEmail = 'test@example.com';

    $email = new Email($mixedCaseEmail);

    expect($email->getValue())->toBe($normalizedEmail)
        ->and((string) $email)->toBe($normalizedEmail);
});

test('should trim whitespace from email', function () {
    $emailWithSpaces = '  test@example.com  ';
    $trimmedEmail = 'test@example.com';

    $email = new Email($emailWithSpaces);

    expect($email->getValue())->toBe($trimmedEmail)
        ->and((string) $email)->toBe($trimmedEmail);
});

test('should throw an exception for email without @', function () {
    $invalidEmail = 'testexample.com';

    expect(fn () => new Email($invalidEmail))->toThrow(InvalidEmail::class);
});

test('should throw an exception for email without domain', function () {
    $invalidEmail = 'test@';

    expect(fn () => new Email($invalidEmail))->toThrow(InvalidEmail::class);
});

test('should throw an exception for email without local part', function () {
    $invalidEmail = '@example.com';

    expect(fn () => new Email($invalidEmail))->toThrow(InvalidEmail::class);
});

test('should throw an exception for empty email', function () {
    $invalidEmail = '';

    expect(fn () => new Email($invalidEmail))->toThrow(InvalidEmail::class);
});

test('should validate an email correctly', function () {
    $validEmail = 'test@example.com';
    $email      = new Email($validEmail);

    // Use reflection to access the validate method
    $reflectionClass = new ReflectionClass(Email::class);
    $method          = $reflectionClass->getMethod('validate');
    $method->setAccessible(true);

    // This should not throw an exception
    $method->invoke($email, $validEmail);

    // This assertion is just to make sure the test passes
    expect(true)->toBeTrue();
});
