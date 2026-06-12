<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class InfrastructureStatus extends Command
{
    /**
     * @var string
     */
    protected $signature = 'infra:status';

    /**
     * @var string
     */
    protected $description = 'Мониторинг наличия данных и статуса во всех узлах распределенной системы';

    /**
     * Выполняет диагностику всех слоев инфраструктуры.
     */
    public function handle(): int
    {
        $this->info('=== ДИАГНОСТИКА РАСПРЕДЕЛЕННОЙ СИСТЕМЫ ==='.PHP_EOL);

        $this->checkPostgres();
        $this->checkRedis();
        $this->checkDebezium();
        $this->checkClickHouse();

        $this->line(PHP_EOL.'=========================================');

        return Command::SUCCESS;
    }

    /**
     * Проверяет состояние и наполненность таблиц PostgreSQL.
     */
    private function checkPostgres(): void
    {
        try {
            $outboxCount = DB::table('outbox_messages')->count();
            $notificationsCount = DB::table('notifications')->count();

            $this->line(sprintf(
                'PostgreSQL:  [OK]  (Уведомлений в БД: <comment>%d</comment>, Записей в Outbox: <comment>%d</comment>)',
                $notificationsCount,
                $outboxCount
            ));
        } catch (\Exception $e) {
            $this->error('PostgreSQL:  [FAIL] Ошибка подключения: '.$e->getMessage());
        }
    }

    /**
     * Проверяет состояние и количество ключей в Redis.
     */
    private function checkRedis(): void
    {
        try {
            /** @var array<int, string> $keys */
            $keys = Redis::keys('idempotency:*');
            /** @var array<int, string> $msgKeys */
            $msgKeys = Redis::keys('msg:*');

            $totalKeys = count($keys) + count($msgKeys);
            $this->line(sprintf('Redis:       [OK]  (Активных замков и ключей кэша в памяти: <comment>%d</comment>)', $totalKeys));
        } catch (\Exception $e) {
            $this->error('Redis:       [FAIL] Ошибка подключения: '.$e->getMessage());
        }
    }

    /**
     * Проверяет статус репликации коннектора Debezium.
     */
    private function checkDebezium(): void
    {
        $dbzHost = env('DEBEZIUM_HOST', 'debezium');
        $configPath = base_path('debezium-postgres.json');

        if (! file_exists($configPath)) {
            $this->line('Debezium:    [WARN] Файл конфигурации debezium-postgres.json не найден для определения имени.');

            return;
        }

        try {
            $connectorName = 'outbox-connector';
            $url = "http://{$dbzHost}:8083/connectors/{$connectorName}/status";
            $response = Http::get($url);

            if ($response->status() === 200) {
                $responseData = $response->json();
                $state = $responseData['connector']['state'] ?? 'UNKNOWN';
                $taskState = $responseData['tasks']['state'] ?? 'UNKNOWN';

                $this->line(sprintf(
                    'Debezium:    [OK]  (Коннектор: <comment>%s</comment>, Статус: <comment>%s</comment>, Задача: <comment>%s</comment>)',
                    $connectorName,
                    $state,
                    $taskState
                ));
            } else {
                $this->line(sprintf('Debezium:    [WARN] Коннектор %s зарегистрирован, но сервер вернул код %d', $connectorName, $response->status()));
            }
        } catch (\Exception $e) {
            $this->error('Debezium:    [FAIL] Не удалось опросить статус коннектора: '.$e->getMessage());
        }
    }

    /**
     * Проверяет авторизацию и количество аналитических отчетов в ClickHouse.
     */
    private function checkClickHouse(): void
    {
        $chHost = (string) config('database.connections.clickhouse.host', 'clickhouse');
        $chPort = (string) config('database.connections.clickhouse.port', '8123');
        $chUser = (string) config('database.connections.clickhouse.username', 'default');
        $chPassword = (string) config('database.connections.clickhouse.password', 'secret_password');

        $chUrl = "http://{$chHost}:{$chPort}/?user={$chUser}&password={$chPassword}";

        try {
            $response = Http::withBody('SELECT count() FROM analytics.notifications_report', 'text/plain')
                ->post($chUrl);

            if ($response->successful()) {
                $count = (int) trim($response->body());
                $this->line(sprintf('ClickHouse:  [OK]  (Всего строк аналитических отчетов: <comment>%d</comment>)', $count));
            } else {
                $this->line(sprintf('ClickHouse:  [FAIL] Код: %d, Ошибка: %s', $response->status(), trim($response->body())));
            }
        } catch (\Exception $e) {
            $this->error('ClickHouse:  [FAIL] База аналитики недоступна по сети: '.$e->getMessage());
        }
    }
}
