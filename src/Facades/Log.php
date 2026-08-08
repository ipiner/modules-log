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
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor()
    {
        return 'pin.modules.log';
    }
}
