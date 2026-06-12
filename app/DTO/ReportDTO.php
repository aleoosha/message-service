<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Объект переноса данных (DTO) для отдельной записи аналитического отчета.
 */
readonly class ReportDTO
{
    public function __construct(
        public string $messageId,
        public string $recipient,
        public string $status,
        public string $updatedAt,
    ) {}

    /**
     * Фабричный метод для создания DTO из строки ответа ClickHouse.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromClickHouse(array $data): self
    {
        return new self(
            messageId: (string) ($data['message_id'] ?? ''),
            recipient: (string) ($data['recipient'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            updatedAt: (string) ($data['updated_at'] ?? ''),
        );
    }

    /**
     * Преобразует DTO в плоский массив для JSON-ответов.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'message_id' => $this->messageId,
            'recipient' => $this->recipient,
            'status' => $this->status,
            'updated_at' => $this->updatedAt,
        ];
    }
}
