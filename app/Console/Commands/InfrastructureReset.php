<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class InfrastructureReset extends Command
{
    /**
     * @var string
     */
    protected $signature = 'infra:reset';

    /**
     * @var string
     */
    protected $description = 'Полная очистка и принудительное пересоздание таблиц ClickHouse и коннекторов Debezium';

    /**
     * Выполняет инициализацию и сброс асинхронного контура.
     */
    public function handle(): int
    {
        $this->info('=== СТАРТ ПОЛНОЙ ОЧИСТКИ И ИНИЦИАЛИЗАЦИИ ==='.PHP_EOL);

        $this->resetRedis();
        $this->resetClickHouse();
        $this->resetDebezium();

        $this->info(PHP_EOL.'Вся инфраструктура успешно инициализирована!');

        return Command::SUCCESS;
    }

    /**
     * Сбрасывает оперативную память Redis.
     */
    private function resetRedis(): void
    {
        $this->info('1. Очистка оперативной памяти Redis...');
        try {
            Redis::executeRaw(['FLUSHALL']);
            $this->line('Redis успешно очищен.');
        } catch (\Exception $e) {
            $this->warn('Redis недоступен, пропускаем.');
        }
    }

    /**
     * Инициализирует схемы и очищает данные таблиц в ClickHouse.
     */
    private function resetClickHouse(): void
    {
        $this->info('2. Инициализация и очистка таблиц в ClickHouse...');

        $chHost = (string) config('database.connections.clickhouse.host', 'clickhouse');
        $chPort = (string) config('database.connections.clickhouse.port', '8123');
        $chUser = (string) config('database.connections.clickhouse.username', 'default');
        $chPassword = (string) config('database.connections.clickhouse.password', 'secret_password');

        $chUrl = "http://{$chHost}:{$chPort}/?user={$chUser}&password={$chPassword}";

        try {
            Http::withBody('CREATE DATABASE IF NOT EXISTS analytics', 'text/plain')->post($chUrl);

            Http::withBody('CREATE TABLE IF NOT EXISTS analytics.notifications_report (
                message_id String,
                recipient String,
                status String,
                updated_at DateTime
            ) ENGINE = ReplacingMergeTree(updated_at)
            PRIMARY KEY message_id
            ORDER BY message_id', 'text/plain')->post($chUrl);

            Http::withBody('TRUNCATE TABLE analytics.notifications_report', 'text/plain')->post($chUrl);
            $this->line('ClickHouse успешно подготовлен к работе.');
        } catch (\Exception $e) {
            $this->error('Не удалось подключиться к ClickHouse: '.$e->getMessage());
        }
    }

    /**
     * Удаляет старый коннектор Debezium и регистрирует новый с чистого листа.
     */
    private function resetDebezium(): void
    {
        $this->info('3. Сброс и регистрация смещений Debezium...');

        $dbzHost = env('DEBEZIUM_HOST', 'debezium');
        $connectorName = 'outbox-connector';
        $url = "http://{$dbzHost}:8083/connectors/{$connectorName}";

        $checkResponse = Http::get($url);

        if ($checkResponse->status() === 200) {
            $this->line('Обнаружен старый коннектор. Удаляем...');
            Http::delete($url);

            $attempts = 0;
            while ($attempts < 30) {
                if (Http::get($url)->status() === 404) {
                    break;
                }
                usleep(100000);
                $attempts++;
            }
        }

        $configPath = base_path('debezium-postgres.json');
        if (! file_exists($configPath)) {
            $this->error("Файл конфигурации коннектора не найден по пути: {$configPath}");

            return;
        }

        $this->line('Регистрируем чистый коннектор Debezium...');
        $config = json_decode(file_get_contents($configPath), true, 512, JSON_THROW_ON_ERROR);

        $createResponse = Http::post("http://{$dbzHost}:8083/connectors", $config);

        if (! in_array($createResponse->status(), [201, 409], true)) {
            $this->error("Ошибка инициализации Debezium. Код ответа: {$createResponse->status()}");
        }
    }
}
