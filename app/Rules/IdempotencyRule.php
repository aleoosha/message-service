<?php

declare(strict_types=1);

namespace App\Rules;

use App\Services\IdempotencyValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IdempotencyRule implements ValidationRule
{
    /**
     * @param IdempotencyValidator $idempotencyValidator
     */
    public function __construct(
        private IdempotencyValidator $idempotencyValidator
    ) {}

    /**
     * Выполняет проверку валидности ключа идемпотентности.
     *
     * @param string $attribute
     * @param mixed $value
     * @param Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $userIds = request()->input('user_ids', []);
        $ints = array_map(fn ($id) => (int) $id, is_array($userIds) ? $userIds : []);

        $stringKey = (string) $value;

        if ($this->idempotencyValidator->isDuplicate($stringKey, $ints)) {
            $fail('Request with this idempotency key was already processed.');
            return;
        }

        $this->idempotencyValidator->registerKey($stringKey);
    }
}
