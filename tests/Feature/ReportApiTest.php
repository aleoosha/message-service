<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Collections\ReportCollection;
use App\DTO\ReportDTO;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    RateLimiter::clear('analytics_api');
});

it('returns analytic reports from clickhouse with 200 status', function () {
    $dto = new ReportDTO(
        messageId: '300822ca-664a-4f43-8662-e75afd5d715b',
        recipient: 'client_success_unique_1',
        status: 'sent',
        updatedAt: '2026-06-11 10:48:10'
    );

    $mockCollection = new ReportCollection([$dto]);

    $this->mock(ReportRepositoryInterface::class)
        ->shouldReceive('getByRecipient')
        ->once()
        ->with('client_success_unique_1')
        ->andReturn($mockCollection);

    $response = $this->getJson('/api/v1/reports/client_success_unique_1');

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [
                [
                    'message_id' => '300822ca-664a-4f43-8662-e75afd5d715b',
                    'recipient' => 'client_success_unique_1',
                    'status' => 'sent',
                    'updated_at' => '2026-06-11 10:48:10',
                ],
            ],
        ]);
});

it('returns 404 when no reports found for recipient', function () {
    $this->mock(ReportRepositoryInterface::class)
        ->shouldReceive('getByRecipient')
        ->once()
        ->with('client_unknown_unique_2')
        ->andReturn(new ReportCollection([]));

    $response = $this->getJson('/api/v1/reports/client_unknown_unique_2');

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
        ->andReturn(new ReportCollection([]));

    $recipientKey = 'client_flood_unique_3';

    $targetIp = '10.20.30.40';

    // 1. Совершаем 100 честных последовательных запросов, забивая лимит до максимума
    for ($i = 0; $i < 100; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => $targetIp])
            ->getJson("/api/v1/reports/{$recipientKey}")
            ->assertStatus(404);
    }

    // 2. 101-й запрос от этого же клиента гарантированно упирается в 429 Too Many Requests
    $response = $this->withServerVariables(['REMOTE_ADDR' => $targetIp])
        ->getJson("/api/v1/reports/{$recipientKey}");

    $response->assertStatus(429);
});
