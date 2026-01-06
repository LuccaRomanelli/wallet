<?php

use App\Enums\TransactionStatus;
use App\Jobs\SendTransferNotificationJob;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    Http::fake([
        'https://util.devi.tools/api/v2/authorize' => Http::response([
            'status' => 'success',
            'data' => ['authorization' => true],
        ], 200),
        'https://util.devi.tools/api/v1/notify' => Http::response([
            'status' => 'success',
        ], 200),
    ]);
});

test('transfer is successful between common users', function () {
    $payer = User::factory()->common()->create(['start_money' => 100000]);
    $payee = User::factory()->common()->create(['start_money' => 0]);

    $response = $this->postJson('/api/transfer', [
        'value' => 100.00,
        'payer' => $payer->id,
        'payee' => $payee->id,
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'message' => 'Transfer completed successfully.',
            'data' => [
                'payer_id' => $payer->id,
                'payee_id' => $payee->id,
                'amount' => '100.00',
                'status' => 'completed',
            ],
        ]);

    $this->assertDatabaseHas('transactions', [
        'payer_id' => $payer->id,
        'payee_id' => $payee->id,
        'amount' => 10000,
        'status' => TransactionStatus::Completed->value,
    ]);

    Queue::assertPushed(SendTransferNotificationJob::class);
});

test('transfer is successful from common user to merchant', function () {
    $payer = User::factory()->common()->create(['start_money' => 50000]);
    $payee = User::factory()->merchant()->create(['start_money' => 0]);

    $response = $this->postJson('/api/transfer', [
        'value' => 250.50,
        'payer' => $payer->id,
        'payee' => $payee->id,
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'message' => 'Transfer completed successfully.',
            'data' => [
                'payer_id' => $payer->id,
                'payee_id' => $payee->id,
                'amount' => '250.50',
                'status' => 'completed',
            ],
        ]);
});

test('merchant cannot send transfer', function () {
    $payer = User::factory()->merchant()->create(['start_money' => 100000]);
    $payee = User::factory()->common()->create(['start_money' => 0]);

    $response = $this->postJson('/api/transfer', [
        'value' => 100.00,
        'payer' => $payer->id,
        'payee' => $payee->id,
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'message' => 'Merchants are not allowed to send transfers.',
        ]);

    $this->assertDatabaseMissing('transactions', [
        'payer_id' => $payer->id,
        'payee_id' => $payee->id,
    ]);
});

test('transfer fails with insufficient balance', function () {
    $payer = User::factory()->common()->create(['start_money' => 5000]);
    $payee = User::factory()->common()->create(['start_money' => 0]);

    $response = $this->postJson('/api/transfer', [
        'value' => 100.00,
        'payer' => $payer->id,
        'payee' => $payee->id,
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Insufficient balance to complete the transfer.',
        ]);

    $this->assertDatabaseMissing('transactions', [
        'payer_id' => $payer->id,
        'payee_id' => $payee->id,
    ]);
});

test('transfer fails when payer does not exist', function () {
    $payee = User::factory()->common()->create(['start_money' => 0]);

    $response = $this->postJson('/api/transfer', [
        'value' => 100.00,
        'payer' => 99999,
        'payee' => $payee->id,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['payer']);
});

test('transfer fails when payee does not exist', function () {
    $payer = User::factory()->common()->create(['start_money' => 100000]);

    $response = $this->postJson('/api/transfer', [
        'value' => 100.00,
        'payer' => $payer->id,
        'payee' => 99999,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['payee']);
});

test('self transfer is rejected', function () {
    $user = User::factory()->common()->create(['start_money' => 100000]);

    $response = $this->postJson('/api/transfer', [
        'value' => 100.00,
        'payer' => $user->id,
        'payee' => $user->id,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['payee']);
});

test('transfer fails when authorization is denied', function () {
    $payer = User::factory()->common()->create(['start_money' => 100000]);
    $payee = User::factory()->common()->create(['start_money' => 0]);

    $authService = Mockery::mock(\App\Services\Contracts\AuthorizationServiceInterface::class);
    $authService->shouldReceive('authorize')
        ->andThrow(new \App\Exceptions\Transfer\TransferAuthorizationException('Transfer not authorized.'));

    $this->app->instance(\App\Services\Contracts\AuthorizationServiceInterface::class, $authService);

    $response = $this->postJson('/api/transfer', [
        'value' => 100.00,
        'payer' => $payer->id,
        'payee' => $payee->id,
    ]);

    $response->assertStatus(503)
        ->assertJson([
            'message' => 'Transfer not authorized.',
        ]);

    $this->assertDatabaseMissing('transactions', [
        'payer_id' => $payer->id,
        'payee_id' => $payee->id,
    ]);

    Mockery::close();
});

test('transfer fails when authorization service is unavailable', function () {
    $payer = User::factory()->common()->create(['start_money' => 100000]);
    $payee = User::factory()->common()->create(['start_money' => 0]);

    $authService = Mockery::mock(\App\Services\Contracts\AuthorizationServiceInterface::class);
    $authService->shouldReceive('authorize')
        ->andThrow(new \App\Exceptions\Transfer\TransferAuthorizationException('Authorization service unavailable.'));

    $this->app->instance(\App\Services\Contracts\AuthorizationServiceInterface::class, $authService);

    $response = $this->postJson('/api/transfer', [
        'value' => 100.00,
        'payer' => $payer->id,
        'payee' => $payee->id,
    ]);

    $response->assertStatus(503)
        ->assertJson([
            'message' => 'Authorization service unavailable.',
        ]);

    Mockery::close();
});

test('validation fails for missing value', function () {
    $payer = User::factory()->common()->create(['start_money' => 100000]);
    $payee = User::factory()->common()->create(['start_money' => 0]);

    $response = $this->postJson('/api/transfer', [
        'payer' => $payer->id,
        'payee' => $payee->id,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['value']);
});

test('validation fails for negative value', function () {
    $payer = User::factory()->common()->create(['start_money' => 100000]);
    $payee = User::factory()->common()->create(['start_money' => 0]);

    $response = $this->postJson('/api/transfer', [
        'value' => -100.00,
        'payer' => $payer->id,
        'payee' => $payee->id,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['value']);
});

test('validation fails for zero value', function () {
    $payer = User::factory()->common()->create(['start_money' => 100000]);
    $payee = User::factory()->common()->create(['start_money' => 0]);

    $response = $this->postJson('/api/transfer', [
        'value' => 0,
        'payer' => $payer->id,
        'payee' => $payee->id,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['value']);
});

test('notification job is dispatched to notifications queue', function () {
    $payer = User::factory()->common()->create(['start_money' => 100000]);
    $payee = User::factory()->common()->create(['start_money' => 0]);

    $this->postJson('/api/transfer', [
        'value' => 100.00,
        'payer' => $payer->id,
        'payee' => $payee->id,
    ]);

    Queue::assertPushedOn('notifications', SendTransferNotificationJob::class);
});

test('balance is correctly updated after transfer', function () {
    $payer = User::factory()->common()->create(['start_money' => 100000]);
    $payee = User::factory()->common()->create(['start_money' => 50000]);

    $this->postJson('/api/transfer', [
        'value' => 300.00,
        'payer' => $payer->id,
        'payee' => $payee->id,
    ]);

    $payerBalance = app(\App\Services\WalletBalanceService::class)->calculateBalance($payer->id);
    $payeeBalance = app(\App\Services\WalletBalanceService::class)->calculateBalance($payee->id);

    expect($payerBalance->getCents())->toBe(70000)
        ->and($payeeBalance->getCents())->toBe(80000);
});
