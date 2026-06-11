<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'Message Service',
        'status' => 'OK',
    ]);
});
