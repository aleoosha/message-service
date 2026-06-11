<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Трейт для унификации структуры HTTP JSON-ответов API.
 */
trait ApiResponse
{
    /**
     * Формирует успешный JSON-ответ.
     *
     * @param  array<int|string, mixed>|null  $data
     */
    protected function success(?array $data, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Формирует JSON-ответ об ошибке.
     *
     * @param  array<int|string, mixed>|null  $data
     */
    protected function error(string $message, int $code, ?array $data = null): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => $data,
        ], $code);
    }
}
