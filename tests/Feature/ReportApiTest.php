<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Collections\ReportCollection;
use App\DTO\ReportDTO;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Redis::spy();
    }

    public function test_it_returns_analytic_reports_from_clickhouse_with_200_status(): void
    {
        $recipient = 'client_success_unique_1';

        $mockRepository = Mockery::mock(ReportRepositoryInterface::class);
        
        $mockRepository->shouldReceive('getByRecipient')
            ->once()
            ->with($recipient, 1, null)
            ->andReturn(new ReportCollection([
                new ReportDTO('uuid-1', $recipient, 'sent', '2026-06-12T11:58:50.000000')
            ]));

        $this->app->instance(ReportRepositoryInterface::class, $mockRepository);

        $response = $this->getJson("/api/v1/reports/{$recipient}?limit=1");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Action completed successfully',
                'data' => [
                    'items' => [
                        [
                            'message_id' => 'uuid-1',
                            'recipient' => $recipient,
                            'status' => 'sent',
                            'updated_at' => '2026-06-12T11:58:50.000000'
                        ]
                    ],
                    'meta' => [
                        'limit' => 1,
                        'next_cursor' => '2026-06-12T11:58:50.000000'
                    ]
                ]
            ]);
    }

    public function test_it_returns_404_when_no_reports_found_for_recipient(): void
    {
        $recipient = 'unknown_recipient';

        $mockRepository = Mockery::mock(ReportRepositoryInterface::class);
        
        $mockRepository->shouldReceive('getByRecipient')
            ->once()
            ->with($recipient, 15, null)
            ->andReturn(new ReportCollection([]));

        $this->app->instance(ReportRepositoryInterface::class, $mockRepository);

        $response = $this->getJson("/api/v1/reports/{$recipient}");

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'No reports found for this recipient',
                'data' => null
            ]);
    }
}
