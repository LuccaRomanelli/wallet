<?php

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('store is created successfully with valid data', function () {
    $response = $this->postJson('/api/stores', [
        'name' => 'My Store',
        'email' => 'store@example.com',
        'password' => 'password123',
        'document' => '11222333000181',
        'start_money' => 500.00,
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'message' => 'Store created successfully.',
            'data' => [
                'name' => 'My Store',
                'email' => 'store@example.com',
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

test('store is created with zero start_money when not provided', function () {
    $response = $this->postJson('/api/stores', [
        'name' => 'My Store',
        'email' => 'store@example.com',
        'password' => 'password123',
        'document' => '11222333000181',
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('users', [
        'email' => 'store@example.com',
        'start_money' => 0,
    ]);
});

test('store is created as merchant type', function () {
    $response = $this->postJson('/api/stores', [
        'name' => 'My Store',
        'email' => 'store@example.com',
        'password' => 'password123',
        'document' => '11222333000181',
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('users', [
        'email' => 'store@example.com',
        'user_type' => UserType::Merchant->value,
    ]);
});

test('validation fails when name is missing', function () {
    $response = $this->postJson('/api/stores', [
        'email' => 'store@example.com',
        'password' => 'password123',
        'document' => '11222333000181',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('validation fails when email is missing', function () {
    $response = $this->postJson('/api/stores', [
        'name' => 'My Store',
        'password' => 'password123',
        'document' => '11222333000181',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('validation fails when email is invalid', function () {
    $response = $this->postJson('/api/stores', [
        'name' => 'My Store',
        'email' => 'invalid-email',
        'password' => 'password123',
        'document' => '11222333000181',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('validation fails when email is already taken', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->postJson('/api/stores', [
        'name' => 'My Store',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'document' => '11222333000181',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('validation fails when password is missing', function () {
    $response = $this->postJson('/api/stores', [
        'name' => 'My Store',
        'email' => 'store@example.com',
        'document' => '11222333000181',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('validation fails when password is too short', function () {
    $response = $this->postJson('/api/stores', [
        'name' => 'My Store',
        'email' => 'store@example.com',
        'password' => 'short',
        'document' => '11222333000181',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('validation fails when document is missing', function () {
    $response = $this->postJson('/api/stores', [
        'name' => 'My Store',
        'email' => 'store@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['document']);
});

test('validation fails when document is already taken', function () {
    User::factory()->create(['document' => '11222333000181']);

    $response = $this->postJson('/api/stores', [
        'name' => 'My Store',
        'email' => 'store@example.com',
        'password' => 'password123',
        'document' => '11222333000181',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['document']);
});

test('validation fails when start_money is negative', function () {
    $response = $this->postJson('/api/stores', [
        'name' => 'My Store',
        'email' => 'store@example.com',
        'password' => 'password123',
        'document' => '11222333000181',
        'start_money' => -100.00,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['start_money']);
});
