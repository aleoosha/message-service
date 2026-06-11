<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Kafka\Handlers\ProcessNotificationHandler;
use Illuminate\Console\Command;
use Junges\Kafka\Facades\Kafka;

/**
 * Консольная команда для запуска фонового демона-потребителя сообщений из Apache Kafka.
 */
class ConsumeKafkaCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:consume-kafka {--topic=high}';

    /**
     * @var string
     */
    protected $description = 'Запуск автономного воркера Кафки для обработки уведомлений';

    /**
     * Выполняет инициализацию и запуск бесконечного цикла чтения топика.
     */
    public function handle(): int
    {
        $priority = (string) $this->option('topic');

        $this->info("Воркер запущен. Фильтруем приоритет: {$priority}");

        config(['app.current_worker_priority' => $priority]);

        $consumer = Kafka::consumer(['cdc.public.outbox_messages'])
            ->withConsumerGroupId("message_service_group_{$priority}")
            ->withHandler(new ProcessNotificationHandler)
            ->withAutoCommit()
            ->withOptions([
                'auto.offset.reset' => 'earliest',
                'log_level' => (string) LOG_DEBUG,
            ])
            ->build();

        $consumer->consume();

        return 0;
    }
}
