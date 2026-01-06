<?php

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('common user account is created successfully with valid data', function () {
    $response = $this->postJson('/api/accounts', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'document' => '52998224725',
        'start_money' => 100.00,
        'user_type' => 'common',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'message' => 'Account created successfully.',
            'data' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'user_type' => 'common',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'document' => '52998224725',
        'user_type' => UserType::Common->value,
        'start_money' => 10000,
    ]);
});

test('merchant account is created successfully with valid data', function () {
    $response = $this->postJson('/api/accounts', [
        'name' => 'My Store',
        'email' => 'store@example.com',
        'password' => 'password123',
        'document' => '11222333000181',
        'start_money' => 500.00,
        'user_type' => 'merchant',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'message' => 'Account created successfully.',
            'data' => [
                'name' => 'My Store',
                'email' => 'store@example.com',
                'user_type' => 'merchant',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'name' => 'My Store',
        'email' => 'store@example.com',
        'document' => '11222333000181',
        'user_type' => UserType::Merchant->value,
        'start_money' => 50000,
    ]);
});

test('account is created with zero start_money when not provided', function () {
    $response = $this->postJson('/api/accounts', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'document' => '52998224725',
        'user_type' => 'common',
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('users', [
        'email' => 'jane@example.com',
        'start_money' => 0,
    ]);
});

test('validation fails when user_type is missing', function () {
    $response = $this->postJson('/api/accounts', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'document' => '52998224725',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['user_type']);
});

test('validation fails when user_type is invalid', function () {
    $response = $this->postJson('/api/accounts', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'document' => '52998224725',
        'user_type' => 'invalid',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['user_type']);
});

test('validation fails when name is missing', function () {
    $response = $this->postJson('/api/accounts', [
        'email' => 'john@example.com',
        'password' => 'password123',
        'document' => '52998224725',
        'user_type' => 'common',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('validation fails when email is missing', function () {
    $response = $this->postJson('/api/accounts', [
        'name' => 'John Doe',
        'password' => 'password123',
        'document' => '52998224725',
        'user_type' => 'common',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('validation fails when email is invalid', function () {
    $response = $this->postJson('/api/accounts', [
        'name' => 'John Doe',
        'email' => 'invalid-email',
        'password' => 'password123',
        'document' => '52998224725',
        'user_type' => 'common',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('validation fails when email is already taken', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->postJson('/api/accounts', [
        'name' => 'John Doe',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'document' => '52998224725',
        'user_type' => 'common',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('validation fails when password is missing', function () {
    $response = $this->postJson('/api/accounts', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'document' => '52998224725',
        'user_type' => 'common',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('validation fails when password is too short', function () {
    $response = $this->postJson('/api/accounts', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'short',
        'document' => '52998224725',
        'user_type' => 'common',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('validation fails when document is missing', function () {
    $response = $this->postJson('/api/accounts', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'user_type' => 'common',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['document']);
});

test('validation fails when document is already taken', function () {
    User::factory()->create(['document' => '52998224725']);

    $response = $this->postJson('/api/accounts', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'document' => '52998224725',
        'user_type' => 'common',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['document']);
});

test('validation fails when start_money is negative', function () {
    $response = $this->postJson('/api/accounts', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'document' => '52998224725',
        'start_money' => -100.00,
        'user_type' => 'common',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['start_money']);
});
