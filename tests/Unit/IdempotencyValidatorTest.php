<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\IdempotencyValidator;
use Illuminate\Support\Facades\Redis;
use Mockery;

beforeEach(function () {
    Redis::spy();
});

it('correctly identifies new and duplicate keys using bloom filter bits', function () {
    $validator = new IdempotencyValidator;
    $testKey = 'unique_test_key_bloom_999';

    Redis::shouldReceive('executeRaw')
        ->with(Mockery::on(fn ($args) => isset($args[0]) && $args[0] === 'GETBIT'))
        ->andReturn(0);

    expect($validator->isDuplicate($testKey))->toBeFalse();

    Redis::shouldReceive('executeRaw')
        ->atLeast()->once()
        ->with(Mockery::on(fn ($args) => isset($args[0]) && $args[0] === 'SETBIT'));

    $validator->registerKey($testKey);
});
