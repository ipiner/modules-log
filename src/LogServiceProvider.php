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
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->app->singleton('pin.modules.log', Log::class);

        $this->mergeConfigFrom(__DIR__.'/../config/log.php', 'pin.modules.log');
        $this->publishes(
            [__DIR__.'/../config/log.php' => config_path('pin/modules/log.php')],
            'pin-modules-log-config'
        );
        $this->publishes(
            [__DIR__.'/../database/migrations' => database_path('migrations')],
            'pin-modules-log-migrations'
        );

        // 自动注册日志路由
        LogRoute::registerRoutes();
    }
}
