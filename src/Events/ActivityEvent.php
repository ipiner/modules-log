<?php

declare(strict_types=1);

namespace Pin\Modules\Log\Events;

use Pin\Support\Str;

/**
 * 行为日志枚举
 */
trait ActivityEvent
{
    /**
     * 事件名称
     */
    public function event(): string
    {
        return $this->parts()[0];
    }

    /**
     * 事件标题
     */
    public function title(array $replacement = []): string
    {
        return Str::format($this->parts()[1], $replacement);
    }

    /**
     * 操作对象类型
     */
    public function subjectType(): string
    {
        $parts = $this->parts();
        $event = $parts[0];

        return $parts[2] ?? explode('.', $event)[0];
    }

    /**
     * 分割值
     */
    protected function parts(): array
    {
        return explode('|', $this->value);
    }
}
