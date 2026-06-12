<?php

declare(strict_types=1);

namespace App\Kafka\Handlers;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageConsumer;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

/**
 * Обработчик сообщений из Apache Kafka с поддержкой контентной фильтрации, ретраев и рандома сбоев.
 */
class ProcessNotificationHandler
{
    private const int LOCK_TTL = 60;

    private const int STATUS_TTL = 86400;

    private const int FAIL_PROBABILITY_PERCENT = 20;

    private const string TRIGGER_FORCE_SEND = 'FORCE_SEND';

    private const string TRIGGER_SPAM_BLOCK = 'SPAM_BLOCK';

    /**
     * Выполняет обработку полученного сообщения из CDC-очереди.
     */
    public function __invoke(ConsumerMessage $message, MessageConsumer $consumer): void
    {
        $cdcEnvelope = $message->getBody();
        $payload = $cdcEnvelope['payload']['after'] ?? null;

        if (! $payload) {
            Log::warning('Kafka Worker: Received empty or invalid CDC envelope.');

            return;
        }

        $notification = json_decode((string) $payload['payload'], true, 512, JSON_THROW_ON_ERROR);
        $messageId = (string) $notification['id'];
        $text = (string) ($notification['text'] ?? '');
        
        $recipient = $this->extractRecipient($notification);

        $workerPriority = config('app.current_worker_priority');
        $messagePriority = (string) $payload['type'];

        if ($workerPriority && $messagePriority !== $workerPriority) {
            return;
        }

        if ($this->isAlreadyProcessed($messageId)) {
            return;
        }

        if (str_contains($text, self::TRIGGER_SPAM_BLOCK)) {
            $this->dropMessage($messageId, $recipient, 'Blocked by spam trigger.');

            return;
        }

        if (! $this->acquireLock($messageId)) {
            return;
        }

        try {
            $this->sendToExternalProvider($notification, $text);
            $this->markAsProcessed($messageId, $recipient);
        } catch (Exception $exception) {
            $this->handleFailure($messageId, $recipient, $exception);
        } finally {
            $this->releaseLock($messageId);
        }
    }

    /**
     * Проверяет, было ли данное сообщение уже успешно обработано.
     */
    private function isAlreadyProcessed(string $messageId): bool
    {
        return Redis::get("msg:status:{$messageId}") === 'done';
    }

    /**
     * Пытается установить атомарную блокировку для предотвращения race condition.
     *
     * @param string $messageId
     * @return bool
     */
    private function acquireLock(string $messageId): bool
    {
        $result = Redis::executeRaw([
            'SET', 
            "msg:lock:{$messageId}", 
            'processing', 
            'NX', 
            'EX', 
            (string) self::LOCK_TTL
        ]);

        return $result === true || $result === 'OK';
    }

    /**
     * Освобождает ранее установленную атомарную блокировку.
     */
    private function releaseLock(string $messageId): void
    {
        Redis::del("msg:lock:{$messageId}");
    }

    /**
     * Фиксирует успешную отправку сообщения в кэше и отправляет событие изменения статуса.
     */
    private function markAsProcessed(string $messageId, string $recipient): void
    {
        $this->emitStatusEvent($messageId, $recipient, 'sent');
        Redis::setex("msg:status:{$messageId}", self::STATUS_TTL, 'done');
    }

    /**
     * Принудительно отбрасывает сообщение без попыток переотправки.
     */
    private function dropMessage(string $messageId, string $recipient, string $reason): void
    {
        Log::notice("Message {$messageId} dropped: {$reason}");
        $this->emitStatusEvent($messageId, $recipient, 'dropped');
        Redis::setex("msg:status:{$messageId}", self::STATUS_TTL, 'done');
    }

    /**
     * Обрабатывает сбои при отправке сообщений, управляя логикой повторов через Кафку.
     *
     * @throws Exception
     */
    private function handleFailure(string $messageId, string $recipient, Exception $exception): void
    {
        Log::error("Failed to process message {$messageId}: {$exception->getMessage()}");

        if ($this->isTemporaryError($exception)) {
            $this->releaseLock($messageId);
            throw $exception;
        }

        $this->emitStatusEvent($messageId, $recipient, 'dropped');
        Redis::setex("msg:status:{$messageId}", self::STATUS_TTL, 'done');
    }

    /**
     * Имитирует вызов внешнего шлюза со случайными сбоями и учетом триггерных слов.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Exception
     */
    private function sendToExternalProvider(array $data, string $text): void
    {
        usleep(50000);

        if (str_contains($text, self::TRIGGER_FORCE_SEND)) {
            return;
        }

        if (random_int(1, 100) <= self::FAIL_PROBABILITY_PERCENT) {
            throw new Exception('External provider gateway timeout.');
        }
    }

    /**
     * Публикует событие изменения статуса уведомления в Apache Kafka для аналитики ClickHouse.
     */
    private function emitStatusEvent(string $messageId, string $recipient, string $status): void
    {
        $message = new Message(
            body: [
                'message_id' => $messageId,
                'recipient' => $recipient,
                'status' => $status,
                'updated_at' => now()->toIso8601String(),
            ]
        );

        Kafka::publish()->onTopic('message.statuses')->withMessage($message)->send();
    }

    /**
     * Извлекает реального получателя (ID пользователя) из структуры уведомления.
     *
     * @param array<string, mixed> $notification
     * @return string
     */
    private function extractRecipient(array $notification): string
    {
        if (isset($notification['user_id'])) {
            return (string) $notification['user_id'];
        }

        if (isset($notification['recipient'])) {
            return (string) $notification['recipient'];
        }

        return 'unknown_user';
    }

    /**
     * Определяет, является ли возникшая ошибка временным сетевым сбоем для запуска повтора.
     */
    private function isTemporaryError(Exception $exception): bool
    {
        return true;
    }
}
