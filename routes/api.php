<?php

use App\Http\Controllers\Api\v1\NotificationController;
use App\Http\Middleware\IdempotencyMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'alive']);
});

Route::prefix('v1')->group(function () {

    Route::post('/notifications', [NotificationController::class, 'sendBulk'])
        ->middleware(IdempotencyMiddleware::class);

});
