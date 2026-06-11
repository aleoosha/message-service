<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Traits\ApiResponse;

/**
 * Базовый абстрактный контроллер приложения.
 */
abstract class Controller
{
    use ApiResponse;
}
