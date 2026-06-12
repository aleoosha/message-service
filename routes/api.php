<?php

use App\Http\Controllers\Api\v1\NotificationController;
use App\Http\Controllers\Api\v1\ReportController;
use App\Http\Middleware\IdempotencyMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {

    Route::post('/notifications', [NotificationController::class, 'sendBulk'])
        ->middleware(IdempotencyMiddleware::class);

    Route::get('/reports/{recipient}', [ReportController::class, 'show'])
        ->middleware('throttle:analytics_api');

});
