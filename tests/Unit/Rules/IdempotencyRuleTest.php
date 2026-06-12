<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\IdempotencyRule;
use App\Services\IdempotencyValidator;
use Mockery;

beforeEach(function () {
    $this->validator = Mockery::mock(IdempotencyValidator::class);
    $this->rule = new IdempotencyRule($this->validator);
});

it('passes validation when key is not a duplicate', function () {
    $this->validator->shouldReceive('isDuplicate')
        ->once()
        ->with('new_key_123', [])
        ->andReturn(false);

    $this->validator->shouldReceive('registerKey')
        ->once()
        ->with('new_key_123');

    $failCalled = false;
    $fail = function (string $message) use (&$failCalled) {
        $failCalled = true;
    };

    $this->rule->validate('idempotency_key', 'new_key_123', $fail);

    expect($failCalled)->toBeFalse();
});

it('fails validation and calls closure when key is a duplicate', function () {
    $this->validator->shouldReceive('isDuplicate')
        ->once()
        ->with('duplicate_key_123', [])
        ->andReturn(true);

    $this->validator->shouldNotReceive('registerKey');

    $failCalled = false;
    $fail = function (string $message) use (&$failCalled) {
        $failCalled = true;
        expect($message)->toBe('Request with this idempotency key was already processed.');
    };

    $this->rule->validate('idempotency_key', 'duplicate_key_123', $fail);

    expect($failCalled)->toBeTrue();
});
