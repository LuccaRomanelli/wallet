<?php

use App\Http\Controllers\Api\TransferController;
use Illuminate\Support\Facades\Route;

Route::post('/transfer', TransferController::class);
