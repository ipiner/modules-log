<?php

declare(strict_types=1);

namespace Pin\Modules\Log\Models;

use Pin\Modules\Log\Events\OperationEvent;
use Pin\Modules\Log\Payloads\OperationPayload;

/**
 * 操作日志模型
 *
 * @extends Model<OperationPayload>
 */
class OperationLog extends Model
{
    protected $casts = [
        'changes' => 'array',
    ];

    protected $appends = ['event_name'];

    /**
     * 获取事件对应的名称
     */
    public function getEventNameAttribute(): string
    {
        return OperationEvent::labels()[$this->event] ?? $this->event;
    }
}
