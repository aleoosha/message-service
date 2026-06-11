<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Unit');

pest()->beforeEach(function () {
    Cache::flush();
});
