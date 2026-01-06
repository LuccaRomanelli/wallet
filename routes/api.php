<?php

use App\Http\Controllers\Api\GetUserBalanceController;
use App\Http\Controllers\Api\StoreAccountController;
use App\Http\Controllers\Api\TransferController;
use Illuminate\Support\Facades\Route;

Route::post('/transfer', TransferController::class);
Route::post('/accounts', StoreAccountController::class);
Route::get('/users/{id}/balance', GetUserBalanceController::class);
