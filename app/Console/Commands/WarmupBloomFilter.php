<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\IdempotencyValidator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class WarmupBloomFilter extends Command
{
    /**
     * Консольная сигнатура команды.
     *
     * @var string
     */
    protected $signature = 'bloom:warmup {--chunk=10000 : Размер батча для обработки}';

    /**
     * Описание команды.
     *
     * @var string
     */
    protected $description = 'Атомарная очистка и потоковый импорт миллионов ключей идемпотентности из PostgreSQL в Блум-фильтр';

    /**
     * Выполняет прогрев фильтра.
     */
    public function handle(IdempotencyValidator $validator): int
    {
        $this->info('=== СТАРТ ИНИЦИАЛИЗАЦИИ БЛУМ-ФИЛЬТРА ===');

        $this->line('Очистка старых данных Блум-фильтра в Redis...');
        Redis::del('idempotency:bloom:notifications');

        $chunkSize = (int) $this->option('chunk');
        $totalProcessed = 0;

        $this->line('Потоковое чтение исторических ключей из PostgreSQL...');

        DB::table('notifications')
            ->select('idempotency_key')
            ->orderBy('id')
            ->lazy($chunkSize)
            ->each(function (object $notification) use ($validator, &$totalProcessed): void {
                $rawKey = (string) $notification->idempotency_key;

                $parts = explode(':', $rawKey);
                $cleanKey = $parts[0];

                $validator->registerKey($cleanKey);
                $totalProcessed++;

                if ($totalProcessed % 50000 === 0) {
                    $this->line("Импортировано ключей: {$totalProcessed}");
                }
            });

        $this->info("=== ПРОГРЕВ УСПЕШНО ЗАВЕРШЕН. Всего перенесено ключей: {$totalProcessed} ===");

        return Command::SUCCESS;
    }
}
