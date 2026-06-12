<?php

declare(strict_types=1);

namespace App\DTO;

readonly class ReportDTO
{
    public function __construct(
        public string $messageId,
        public string $recipient,
        public string $status,
        public string $updatedAt
    ) {}

    /**
     * Создает DTO из сырого массива строки ClickHouse.
     *
     * @param array<string, string> $row
     * @return self
     */
    public static function fromClickHouse(array $row): self
    {
        return new self(
            messageId: $row['message_id'] ?? '',
            recipient: $row['recipient'] ?? '',
            status: $row['status'] ?? '',
            updatedAt: $row['updated_at'] ?? ''
        );
    }
}
