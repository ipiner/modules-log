<?php

declare(strict_types=1);

namespace Pin\Modules\Log\Facades;

use Illuminate\Support\Facades\Facade;
use Pin\Modules\Log\Models\ActivityLog;
use Pin\Modules\Log\Models\LoginLog;
use Pin\Modules\Log\Models\OperationLog;
use Pin\Modules\Log\Payloads\Payload;

/**
 * @method static ActivityLog|LoginLog|OperationLog create(Payload $payload)
 *
 * @see \Pin\Modules\Log\Log
 */
class Log extends Facade
{
    /**
     * 日志服务的容器绑定名称。
     */
    protected static function getFacadeAccessor(): string
    {
        return 'pin.modules.log';
    }
}
