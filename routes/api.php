<?php

use App\Http\Controllers\Api\GetUserBalanceController;
use App\Http\Controllers\Api\StoreStoreController;
use App\Http\Controllers\Api\StoreUserController;
use App\Http\Controllers\Api\TransferController;
use Illuminate\Support\Facades\Route;

Route::post('/transfer', TransferController::class);
Route::post('/users', StoreUserController::class);
Route::post('/stores', StoreStoreController::class);
Route::get('/users/{id}/balance', GetUserBalanceController::class);
