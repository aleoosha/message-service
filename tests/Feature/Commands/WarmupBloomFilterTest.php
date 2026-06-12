<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    Redis::spy();
    DB::table('notifications')->truncate();
});

it('successfully clears bloom filter and reimports clean keys from database', function () {
    DB::table('notifications')->insert([
        [
            'uuid' => '600822ca-664a-4f43-8662-e75afd5d715a',
            'idempotency_key' => 'batch_key_100:901',
            'user_id' => 901,
            'text' => 'Test 1',
            'channel' => 'sms',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'uuid' => '700822ca-664a-4f43-8662-e75afd5d715a',
            'idempotency_key' => 'batch_key_100:902',
            'user_id' => 902,
            'text' => 'Test 2',
            'channel' => 'sms',
            'created_at' => now(),
            'updated_at' => now(),
        ]
    ]);

    Redis::shouldReceive('del')
        ->once()
        ->with('idempotency:bloom:notifications')
        ->andReturn(1);

    Redis::shouldReceive('executeRaw')
        ->atLeast()->once()
        ->with(\Mockery::on(fn ($args) => is_array($args) && $args[0] === 'SETBIT'));

    $this->artisan('bloom:warmup --chunk=10')
        ->assertExitCode(0)
        ->expectsOutput('=== СТАРТ ИНИЦИАЛИЗАЦИИ БЛУМ-ФИЛЬТРА ===')
        ->expectsOutput('=== ПРОГРЕВ УСПЕШНО ЗАВЕРШЕН. Всего перенесено ключей: 2 ===');
});
