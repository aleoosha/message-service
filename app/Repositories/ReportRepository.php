<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Collections\ReportCollection;
use App\DTO\ReportDTO;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Support\Facades\Http;

class ReportRepository implements ReportRepositoryInterface
{
    private string $url;

    public function __construct()
    {
        $chHost = (string) config('database.connections.clickhouse.host', 'clickhouse');
        $chPort = (string) config('database.connections.clickhouse.port', '8123');
        $chUser = (string) config('database.connections.clickhouse.username', 'default');
        $chPassword = (string) config('database.connections.clickhouse.password', 'secret_password');

        $this->url = "http://{$chHost}:{$chPort}/?user={$chUser}&password={$chPassword}";
    }

    public function getByRecipient(string $recipient, int $limit = 15, ?string $nextCursor = null): ReportCollection
    {
        $cleanRecipient = trim($recipient);
        $whereConditions = ["recipient = '{$cleanRecipient}'"];

        if ($nextCursor !== null && $nextCursor !== '') {
            $whereConditions[] = "updated_at < '{$nextCursor}'";
        }

        $whereClause = implode(' AND ', $whereConditions);

        $sql = "SELECT message_id, recipient, status, toString(updated_at) as updated_at 
                FROM analytics.notifications_report 
                WHERE {$whereClause} 
                ORDER BY updated_at DESC
                LIMIT {$limit}
                FORMAT JSON";

        $response = Http::withBody($sql, 'text/plain')->post($this->url);

        if (! $response->successful()) {
            return new ReportCollection;
        }

        $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        $rows = $data['data'] ?? [];

        $dtos = array_map(
            fn (array $row) => ReportDTO::fromClickHouse($row),
            $rows
        );

        return new ReportCollection($dtos);
    }
}
