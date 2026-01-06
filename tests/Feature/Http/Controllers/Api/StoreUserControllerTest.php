<?php

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user is created successfully with valid data', function () {
    $response = $this->postJson('/api/users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'document' => '52998224725',
        'start_money' => 100.00,
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'message' => 'User created successfully.',
            'data' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
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

test('user is created with zero start_money when not provided', function () {
    $response = $this->postJson('/api/users', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'document' => '52998224725',
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('users', [
        'email' => 'jane@example.com',
        'start_money' => 0,
    ]);
});

test('user is created as common type', function () {
    $response = $this->postJson('/api/users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'document' => '52998224725',
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
        'user_type' => UserType::Common->value,
    ]);
});

test('validation fails when name is missing', function () {
    $response = $this->postJson('/api/users', [
        'email' => 'john@example.com',
        'password' => 'password123',
        'document' => '52998224725',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('validation fails when email is missing', function () {
    $response = $this->postJson('/api/users', [
        'name' => 'John Doe',
        'password' => 'password123',
        'document' => '52998224725',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('validation fails when email is invalid', function () {
    $response = $this->postJson('/api/users', [
        'name' => 'John Doe',
        'email' => 'invalid-email',
        'password' => 'password123',
        'document' => '52998224725',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('validation fails when email is already taken', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->postJson('/api/users', [
        'name' => 'John Doe',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'document' => '52998224725',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('validation fails when password is missing', function () {
    $response = $this->postJson('/api/users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'document' => '52998224725',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('validation fails when password is too short', function () {
    $response = $this->postJson('/api/users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'short',
        'document' => '52998224725',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('validation fails when document is missing', function () {
    $response = $this->postJson('/api/users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['document']);
});

test('validation fails when document is already taken', function () {
    User::factory()->create(['document' => '52998224725']);

    $response = $this->postJson('/api/users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'document' => '52998224725',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['document']);
});

test('validation fails when start_money is negative', function () {
    $response = $this->postJson('/api/users', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'document' => '52998224725',
        'start_money' => -100.00,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['start_money']);
});
