<?php
// filepath: /workspaces/DealSpace/dealspace_api-main/routes/web.php

use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::post(
    'stripe/webhook',
    [WebhookController::class, 'handleWebhook']
)->name('cashier.webhook');