<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Rules\IdempotencyRule;
use App\Traits\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class BulkNotificationRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'idempotency_key' => $this->header('X-Idempotency-Key'),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', app(IdempotencyRule::class)],
            'channel' => ['required', 'string', Rule::in(NotificationChannel::values())],
            'priority' => ['nullable', 'string', Rule::in(NotificationPriority::values())],
            'text' => ['required', 'string', 'max:500'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'integer'],
        ];
    }

    /**
     * Переопределение формата ответа при ошибке валидации через трейт.
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->all();
        $firstError = $errors[0] ?? 'Validation failed.';

        throw new HttpResponseException(
            $this->error(
                message: $firstError,
                code: 422
            )
        );
    }
}
