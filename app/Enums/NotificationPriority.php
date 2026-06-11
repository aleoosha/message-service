<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Перечисление уровней приоритета обработки уведомлений.
 */
enum NotificationPriority: string
{
    case HIGH = 'high';
    case LOW = 'low';

    /**
     * Получает список строковых значений всех вариантов приоритета.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
