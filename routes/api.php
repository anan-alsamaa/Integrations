<?php

use App\Http\Controllers\api\Ketawebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\posKeeta\SaraCallbackController;
use App\Http\Controllers\posKeeta\SaraOrderController; 

Route::post('/order', [Ketawebhook::class, 'postOrder']);

Route::post('/order/updateSaraOrder', [SaraCallbackController::class, 'updateSaraOrder']);