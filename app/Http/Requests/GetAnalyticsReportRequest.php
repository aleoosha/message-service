<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Traits\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class GetAnalyticsReportRequest extends FormRequest
{
    use ApiResponse;

    /**
     * Разрешает доступ к запросу.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации для GET-параметров пагинации аналитики.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'limit'  => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor' => ['nullable', 'string', 'date_format:Y-m-d H:i:s'],
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
