<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Класс валидации входящего HTTP-запроса на массовую отправку уведомлений.
 */
class BulkNotificationRequest extends FormRequest
{
    /**
     * Определяет, имеет ли пользователь право на выполнение этого запроса.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Получает правила валидации, применяемые к запросу.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'channel' => ['required', 'string', Rule::in(NotificationChannel::values())],
            'priority' => ['nullable', 'string', Rule::in(NotificationPriority::values())],
            'text' => ['required', 'string', 'max:500'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'integer'],
        ];
    }
}
