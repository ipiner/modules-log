<?php

declare(strict_types=1);

namespace Pin\Modules\Log;

use Illuminate\Support\Facades\Route;
use Pin\Route\Attributes\Name;
use Pin\Route\Attributes\Prefix;
use Pin\Route\Attributes\Title;
use Pin\Route\InteractsWithRoute;
use Pin\Route\Routable;

/**
 * 登录、操作与行为日志路由。
 */
#[Prefix('$config.pin.modules.log.routes.prefix')]
enum LogRoute: string implements Routable
{
    use InteractsWithRoute;

    #[Title('登录日志')]
    #[Name('$config.pin.modules.log.login.route_name')]
    case LoginLog = 'GET:/logins';

    #[Title('登录日志筛选项')]
    case LoginLogOption = 'GET:/logins/options';

    #[Title('操作日志')]
    #[Name('$config.pin.modules.log.operation.route_name')]
    case OperationLog = 'GET:/operations';

    #[Title('操作日志筛选项')]
    case OperationLogOption = 'GET:/operations/options';

    #[Title('行为日志')]
    #[Name('$config.pin.modules.log.activity.route_name')]
    case ActivityLog = 'GET:/activities';

    #[Title('行为日志筛选项')]
    case ActivityLogOption = 'GET:/activities/options';

    /**
     * 自动注册路由
     */
    public static function registerRoutes(): void
    {
        $config = config('pin.modules.log');

        Route::middleware($config['routes']['middleware'])->group(function () use ($config) {
            foreach ([
                'login' => [self::LoginLog, self::LoginLogOption],
                'operation' => [self::OperationLog, self::OperationLogOption],
                'activity' => [self::ActivityLog, self::ActivityLogOption],
            ] as $name => [$indexRoute, $optionRoute]) {
                if ($config[$name]['route_enabled']) {
                    $controller = $config[$name]['controller'];

                    $indexRoute->register([$controller, 'index']);
                    $optionRoute->register([$controller, 'options']);
                }
            }
        });
    }
}
