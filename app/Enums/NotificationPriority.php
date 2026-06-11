<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationPriority: string
{
    case HIGH = 'high';
    case LOW = 'low';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
