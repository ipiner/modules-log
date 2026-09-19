<?php

declare(strict_types=1);

namespace Pin\Modules\Log;

use Pin\Support\ServiceProvider;

/**
 * 日志服务提供者
 */
class LogServiceProvider extends ServiceProvider
{
    /**
     * 注册配置与服务，供其他服务提供者在启动时使用。
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/log.php', 'pin.modules.log');
        $this->app->singleton(Log::class);
        $this->app->alias(Log::class, 'pin.modules.log');
    }

    public function boot(): void
    {
        $this->publishes(
            [__DIR__.'/../config/log.php' => config_path('pin/modules/log.php')],
            'pin-modules-log-config'
        );
        $this->publishes(
            [__DIR__.'/../database/migrations' => database_path('migrations')],
            'pin-modules-log-migrations'
        );

        // 自动注册日志路由
        if (! $this->app->routesAreCached()) {
            LogRoute::registerRoutes();
        }
    }
}
