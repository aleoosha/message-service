<?php

declare(string_types=1);

namespace Tests\Feature;

use App\DTO\BulkNotificationDTO;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Support\Facades\Redis;
use Mockery;

beforeEach(function () {
    Redis::spy();
});

it('successfully accepts bulk notifications and returns 202', function () {
    $this->mock(NotificationRepositoryInterface::class)
        ->shouldReceive('saveBulk')
        ->once()
        ->with(Mockery::type(BulkNotificationDTO::class));

    Redis::shouldReceive('get')
        ->andReturn(null);

    Redis::shouldReceive('command')
        ->with('set', Mockery::type('array'))
        ->andReturn(true);

    Redis::shouldReceive('setex')
        ->andReturn(true);

    $response = $this->withHeader('X-Idempotency-Key', 'test_key_123')
        ->postJson('/api/v1/notifications', [
            'channel' => 'sms',
            'priority' => 'high',
            'text' => 'Тестовое уведомление Pest v3',
            'user_ids' => [101, 102],
        ]);

    $response->assertStatus(202)
        ->assertJson([
            'status' => 'success',
            'message' => 'Bulk notifications accepted for delivery',
        ]);
});

it('blocks concurrent requests and enforces idempotency key', function () {
    Redis::shouldReceive('get')
        ->andReturn(null);

    Redis::shouldReceive('command')
        ->twice()
        ->with('set', Mockery::type('array'))
        ->andReturn(true, false);

    $payload = [
        'channel' => 'sms',
        'text' => 'Защита от дубликатов',
        'user_ids' => [101],
    ];

    $this->withHeader('X-Idempotency-Key', 'unique_key_777')
        ->postJson('/api/v1/notifications', $payload);

    $response2 = $this->withHeader('X-Idempotency-Key', 'unique_key_777')
        ->postJson('/api/v1/notifications', $payload);

    $response2->assertStatus(409)
        ->assertJson([
            'error' => 'Concurrent request in progress. Please try again.',
        ]);
});
