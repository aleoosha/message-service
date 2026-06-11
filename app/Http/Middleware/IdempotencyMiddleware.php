<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

/**
 * Посредник (Middleware) для обеспечения идемпотентности API-запросов с использованием Redis.
 */
class IdempotencyMiddleware
{
    /**
     * Обрабатывает входящий запрос, проверяя наличие и состояние ключа идемпотентности.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-Idempotency-Key');

        if (! $key) {
            return response()->json(['error' => 'Header X-Idempotency-Key is required'], 400);
        }

        $lockKey = "idempotency:lock:{$key}";
        $responseKey = "idempotency:response:{$key}";

        if ($cachedResponse = Redis::get($responseKey)) {
            return response()->json(json_decode((string) $cachedResponse, true), 200);
        }

        $lockAcquired = Redis::command('set', [$lockKey, 'processing', 'NX', 'EX', 10]);

        if (! $lockAcquired) {
            return response()->json(['error' => 'Concurrent request in progress. Please try again.'], 409);
        }

        try {
            $response = $next($request);

            if ($response->isSuccessful()) {
                $responseData = json_decode($response->getContent() ?: '{}', true);
                Redis::setex($responseKey, 86400, json_encode($responseData));
            }

            return $response;
        } finally {
            Redis::del($lockKey);
        }
    }
}
