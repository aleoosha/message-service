<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    RateLimiter::clear('analytics_api');
});

it('returns analytic reports from clickhouse with 200 status', function () {
    $mockData = [
        [
            'message_id' => '300822ca-664a-4f43-8662-e75afd5d715b',
            'recipient' => 'client_success_unique_1',
            'status' => 'sent',
            'updated_at' => '2026-06-11 10:48:10.000',
        ],
    ];

    $this->mock(ReportRepositoryInterface::class)
        ->shouldReceive('getByRecipient')
        ->once()
        ->with('client_success_unique_1')
        ->andReturn($mockData);

    $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.1'])
        ->getJson('/api/v1/reports/client_success_unique_1');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => $mockData,
        ]);
});

it('returns 404 when no reports found for recipient', function () {
    $this->mock(ReportRepositoryInterface::class)
        ->shouldReceive('getByRecipient')
        ->once()
        ->with('client_unknown_unique_2')
        ->andReturn([]);

    $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.2'])
        ->getJson('/api/v1/reports/client_unknown_unique_2');

    $response->assertStatus(404)
        ->assertJson([
            'status' => 'error',
            'message' => 'No reports found for this recipient',
        ]);
});

it('enforces rate limiting on analytics endpoint after 100 requests', function () {
    $this->mock(ReportRepositoryInterface::class)
        ->shouldReceive('getByRecipient')
        ->zeroOrMoreTimes()
        ->andReturn([]);

    $recipientKey = 'client_flood_unique_3';
    $targetIp = '10.0.0.99';

    // 1. Совершаем 99 честных последовательных запросов от имени ОДНОГО выделенного IP.
    // Это забивает его лимит ровно до 99/100.
    for ($i = 0; $i < 99; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => $targetIp])
            ->getJson("/api/v1/reports/{$recipientKey}")
            ->assertStatus(404);
    }

    // 2. 100-й запрос от этого же IP заполняет лимит до максимума
    $this->withServerVariables(['REMOTE_ADDR' => $targetIp])
        ->getJson("/api/v1/reports/{$recipientKey}")
        ->assertStatus(404);

    // 3. 101-й запрос от этого же IP гарантированно и честно упирается в 429 Too Many Requests!
    $response = $this->withServerVariables(['REMOTE_ADDR' => $targetIp])
        ->getJson("/api/v1/reports/{$recipientKey}");

    $response->assertStatus(429);
});
