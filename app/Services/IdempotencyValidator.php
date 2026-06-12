<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class IdempotencyValidator
{
    private const string BLOOM_FILTER_KEY = 'idempotency:bloom:notifications';

    private const int BIT_ARRAY_SIZE = 16777216;

    /**
     * Проверяет, обрабатывался ли данный ключ идемпотентности ранее.
     *
     * @param  array<int, int>  $userIds
     */
    public function isDuplicate(string $key, array $userIds = []): bool
    {
        if (! $this->maybeExistsInBloom($key)) {
            return false;
        }

        if (! empty($userIds)) {
            $targetKeys = array_map(fn ($id) => $key.':'.$id, $userIds);

            return DB::table('notifications')
                ->whereIn('idempotency_key', $targetKeys)
                ->exists();
        }

        return DB::table('notifications')
            ->where('idempotency_key', 'LIKE', $key.':%')
            ->exists();
    }

    /**
     * Вносит ключ идемпотентности в Фильтр Блума.
     */
    public function registerKey(string $key): void
    {
        foreach ($this->getHashes($key) as $bitOffset) {
            Redis::executeRaw(['SETBIT', self::BLOOM_FILTER_KEY, (string) $bitOffset, '1']);
        }
    }

    /**
     * Проверяет вероятностное наличие ключа в Фильтре Блума.
     */
    private function maybeExistsInBloom(string $key): bool
    {
        foreach ($this->getHashes($key) as $bitOffset) {
            $bitStatus = Redis::executeRaw(['GETBIT', self::BLOOM_FILTER_KEY, (string) $bitOffset]);
            if ($bitStatus === null || (int) $bitStatus === 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Генерирует 3 независимых хэш-смещения для Блум-фильтра.
     *
     * @return array<int, int>
     */
    private function getHashes(string $key): array
    {
        $h1 = crc32($key);
        $h2 = crc32(md5($key));
        $h3 = crc32(sha1($key));

        return [
            abs($h1) % self::BIT_ARRAY_SIZE,
            abs($h2) % self::BIT_ARRAY_SIZE,
            abs($h3) % self::BIT_ARRAY_SIZE,
        ];
    }
}
