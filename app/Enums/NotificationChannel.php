<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Перечисление доступных каналов отправки уведомлений.
 */
enum NotificationChannel: string
{
    case EMAIL = 'email';
    case SMS = 'sms';
    case TELEGRAM = 'telegram';

    /**
     * Получает список строковых значений всех вариантов перечисления.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
