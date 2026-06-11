<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationChannel: string
{
    case EMAIL = 'email';
    case SMS = 'sms';
    case TELEGRAM = 'telegram';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
