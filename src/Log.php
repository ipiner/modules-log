<?php

declare(strict_types=1);

namespace Pin\Modules\Log;

use InvalidArgumentException;
use Pin\Models\Model;
use Pin\Modules\Log\Events\LogEvent;
use Pin\Modules\Log\Models\ActivityLog;
use Pin\Modules\Log\Models\LoginLog;
use Pin\Modules\Log\Models\OperationLog;
use Pin\Modules\Log\Payloads\ActivityPayload;
use Pin\Modules\Log\Payloads\LoginPayload;
use Pin\Modules\Log\Payloads\OperationPayload;
use Pin\Modules\Log\Payloads\Payload;

/**
 * 根据载荷类型写入对应的业务日志。
 */
class Log
{
    /**
     * 创建日志
     *
     * @return ActivityLog|LoginLog|OperationLog
     */
    public function create(Payload $payload): Model
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
            default => throw new InvalidArgumentException(sprintf('不支持的日志载荷类型：%s', $payload::class)),
        };
    }
}
