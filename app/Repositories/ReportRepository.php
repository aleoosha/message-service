<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Support\Facades\Http;

/**
 * Репозиторий для чтения аналитических отчетов из базы данных ClickHouse через HTTP-протокол.
 */
class ReportRepository implements ReportRepositoryInterface
{
    /**
     * Базовый URL для подключения к HTTP API ClickHouse.
     */
    private string $url;

    /**
     * Инициализирует конфигурацию подключения к аналитической базе данных.
     */
    public function __construct()
    {
        $host = (string) env('CLICKHOUSE_HOST', 'clickhouse');
        $port = (string) env('CLICKHOUSE_PORT', '8123');

        $this->url = "http://{$host}:{$port}/";
    }

    /**
     * Получает полную историю статусов уведомлений для конкретного получателя из ClickHouse.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws \JsonException
     */
    public function getByRecipient(string $recipient): array
    {
        $response = Http::post($this->url, [
            'query' => "SELECT message_id, recipient, status, toString(updated_at) as updated_at 
                        FROM analytics.notifications_report 
                        WHERE recipient = '{$recipient}' 
                        ORDER BY updated_at DESC 
                        FORMAT JSON",
        ]);

        if (! $response->successful()) {
            return [];
        }

        $data = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

        return $data['data'] ?? [];
    }
}
