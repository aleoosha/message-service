<?php

declare(strict_types=1);

namespace Tests\Feature\E2E;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

beforeEach(function () {
    Redis::executeRaw(['FLUSHALL']);
    DB::table('outbox_messages')->truncate();
    DB::table('notifications')->truncate();
});

it('enforces idempotency hot cache and cold bloom filter validation rules', function () {
    $idempotencyKey = 'e2e_fresh_key_' . Str::random(8);
    $responseKey = "idempotency:response:{$idempotencyKey}";
    $lockKey = "idempotency:lock:{$idempotencyKey}";
    
    $payload = [
        'channel' => 'sms',
        'priority' => 'high',
        'text' => 'Реальный тест контура валидации',
        'user_ids' => [901, 902]
    ];

    // 1. Первый запрос (Запись данных и создание кэша)
    $response1 = $this->withHeader('X-Idempotency-Key', $idempotencyKey)
        ->postJson('/api/v1/notifications', $payload);

    $response1->assertStatus(202);
    expect(DB::table('notifications')->count())->toBe(2);

    // 2. Горячий повтор (Моментальная отдача из кэша Redis со статусом 200)
    $response2 = $this->withHeader('X-Idempotency-Key', $idempotencyKey)
        ->postJson('/api/v1/notifications', $payload);

    $response2->assertStatus(200);
    expect(DB::table('notifications')->count())->toBe(2);

    // 3. Холодный повтор (Имитируем, что прошло 24 часа и кэш ответа стерт)
    Redis::del($responseKey);
    Redis::executeRaw(['DEL', $lockKey]);

    $response3 = $this->withHeader('X-Idempotency-Key', $idempotencyKey)
        ->postJson('/api/v1/notifications', $payload);

    $response3->assertStatus(422)
        ->assertJson([
            'status' => 'error',
            'message' => 'Request with this idempotency key was already processed.',
            'data' => null
        ]);

    expect(DB::table('notifications')->count())->toBe(2);
});
