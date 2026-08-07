<?php

declare(strict_types=1);

namespace Pin\Modules\Log;

use Pin\Modules\Log\Events\LogEvent;
use Pin\Modules\Log\Models\ActivityLog;
use Pin\Modules\Log\Models\LoginLog;
use Pin\Modules\Log\Models\OperationLog;
use Pin\Modules\Log\Payloads\ActivityPayload;
use Pin\Modules\Log\Payloads\LoginPayload;
use Pin\Modules\Log\Payloads\OperationPayload;
use Pin\Modules\Log\Payloads\Payload;

class Log
{
    /**
     * 创建日志
     *
     * @return ActivityLog|LoginLog|OperationLog
     */
    public function create(Payload $payload)
    {
        $event = $this->resolveEvent($payload);

        return $event->create($payload);
    }

    /**
     * 解析事件
     */
    protected function resolveEvent(Payload $payload): LogEvent
    {
        return match (true) {
            $payload instanceof OperationPayload => LogEvent::Operation,
            $payload instanceof LoginPayload => LogEvent::Login,
            $payload instanceof ActivityPayload => LogEvent::Activity,
        };
    }
}
