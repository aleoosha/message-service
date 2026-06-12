<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    use ApiResponse;

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-Idempotency-Key');

        if (! $key) {
            return $this->error(
                message: 'Header X-Idempotency-Key is required',
                code: 400
            );
        }

        $stringKey = (string) $key;
        $responseKey = "idempotency:response:{$stringKey}";

        $cachedResponse = Redis::get($responseKey);

        if ($cachedResponse !== null && $cachedResponse !== false && $cachedResponse !== '') {
            return response()->json(json_decode((string) $cachedResponse, true), 200);
        }

        $lockKey = "idempotency:lock:{$stringKey}";
        $lockAcquired = Redis::executeRaw(['SET', $lockKey, 'processing', 'NX', 'EX', '10']);

        if (! $lockAcquired || $lockAcquired === 'FALSE') {
            return $this->error(
                message: 'Concurrent request in progress. Please try again.',
                code: 409
            );
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
