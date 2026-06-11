<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Kafka\Handlers\ProcessNotificationHandler;
use Illuminate\Support\Facades\Redis;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;
use Mockery;

beforeEach(function () {
    Redis::spy();
});

/**
 * Вспомогательный хелпер для сборки фейкового CDC-конверта Debezium.
 */
function createFakeCdcMessage(string $text, string $priority = 'high'): ConsumerMessage
{
    $message = Mockery::mock(ConsumerMessage::class);
    $message->shouldReceive('getBody')->andReturn([
        'payload' => [
            'type' => $priority,
            'after' => [
                'type' => $priority,
                'payload' => json_encode([
                    'id' => '029b5846-1153-4be8-b824-f77414ea3957',
                    'user_id' => 901,
                    'text' => $text,
                    'channel' => 'sms',
                    'recipient' => 'live_test_key',
                ], JSON_THROW_ON_ERROR),
            ],
        ],
    ]);

    return $message;
}

it('skips processing if message is already marked as done in redis', function () {
    Redis::shouldReceive('get')
        ->with('msg:status:029b5846-1153-4be8-b824-f77414ea3957')
        ->andReturn('done');

    $handler = new ProcessNotificationHandler;
    $message = createFakeCdcMessage('Обычный текст');
    $consumer = Mockery::mock(MessageConsumer::class);

    config(['app.current_worker_priority' => 'high']);

    $handler($message, $consumer);

    // Проверяем, что замок на запись даже не запрашивался
    Redis::shouldNotReceive('command')->with('set', Mockery::type('array'));
});

it('instantly drops message if SPAM_BLOCK word is triggered', function () {
    $handler = new ProcessNotificationHandler;
    $message = createFakeCdcMessage('Внимание! Это слово содержит SPAM_BLOCK внутри текста');
    $consumer = Mockery::mock(MessageConsumer::class);

    config(['app.current_worker_priority' => 'high']);

    // Ожидаем, что статус выставится в done (сообщение схлопнуто)
    Redis::shouldReceive('setex')
        ->once()
        ->with('msg:status:029b5846-1153-4be8-b824-f77414ea3957', Mockery::type('int'), 'done');

    $handler($message, $consumer);
});
