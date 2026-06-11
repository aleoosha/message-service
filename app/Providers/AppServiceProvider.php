<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\ReportRepositoryInterface;
use App\Repositories\NotificationRepository;
use App\Repositories\ReportRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Регистрация связей интерфейсов и их физических реализаций.
     */
    public function register(): void
    {
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
        $this->app->bind(ReportRepositoryInterface::class, ReportRepository::class);
    }

    /**
     * Инициализация сервисов приложения и лимитеров частоты запросов.
     */
    public function boot(): void
    {
        RateLimiter::for('analytics_api', function (Request $request) {
            return Limit::perMinute(100)->by($request->ip() ?? $request->user()?->id);
        });
    }
}
