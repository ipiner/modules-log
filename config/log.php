<?php

use Pin\Modules\Log\Controllers\ActivityLogController;
use Pin\Modules\Log\Controllers\LoginLogController;
use Pin\Modules\Log\Controllers\OperationLogController;
use Pin\Modules\Log\Events\LogEvent;
use Pin\Modules\Log\Models\ActivityLog;
use Pin\Modules\Log\Models\LoginLog;
use Pin\Modules\Log\Models\OperationLog;

return [
    'routes' => [
        'middleware' => 'auth',
        'prefix' => '/api/system/log',
    ],
    /**
     * 登录日志
     */
    LogEvent::Login->value => [
        'model' => LoginLog::class,
        'controller' => LoginLogController::class,
        'route_enabled' => true,
        'route_name' => 'system.log.logins',
    ],
    /**
     * 操作日志
     */
    LogEvent::Operation->value => [
        'model' => OperationLog::class,
        'controller' => OperationLogController::class,
        'route_enabled' => true,
        'route_name' => 'system.log.operations',
        'subject_name_columns' => [],
    ],
    /**
     * 行为日志
     */
    LogEvent::Activity->value => [
        'model' => ActivityLog::class,
        'controller' => ActivityLogController::class,
        'route_enabled' => true,
        'route_name' => 'system.log.activities',
    ],
];
