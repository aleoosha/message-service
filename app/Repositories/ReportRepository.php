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
        $host = (string) config('database.connections.clickhouse.host', 'clickhouse');
        $port = (string) config('database.connections.clickhouse.port', '8123');
        $user = (string) config('database.connections.clickhouse.username', 'default');
        $password = (string) config('database.connections.clickhouse.password', 'password');

        $this->url = "http://{$host}:{$port}/?user={$user}&password={$password}";
    }

    /**
     * Получает коллекцию DTO отчетов из ClickHouse.
     */
    public function getByRecipient(string $recipient): ReportCollection
    {
        $cleanRecipient = trim($recipient);

        $sql = "SELECT message_id, recipient, status, toString(updated_at) as updated_at 
                FROM analytics.notifications_report 
                WHERE recipient = '{$cleanRecipient}' 
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
