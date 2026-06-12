<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Идентификатор сгенерированного сообщения для конкретного пользователя.
 */
readonly class GeneratedMessageDTO
{
    public function __construct(
        public int $userId,
        public string $messageId
    ) {}
}
