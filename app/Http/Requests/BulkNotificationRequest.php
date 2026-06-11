<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
