<?php

declare(strict_types=1);

namespace Pin\Modules\Log\Events;

use InvalidArgumentException;
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

        return $parts[2] ?? explode('.', $event, 2)[0];
    }

    /**
     * 解析 event|title 或 event|title|subject_type。
     *
     * @return array{string, string, string|null}
     */
    protected function parts(): array
    {
        $parts = explode('|', $this->value, 4);
        if (count($parts) < 2 || count($parts) > 3 || $parts[0] === '') {
            throw new InvalidArgumentException('行为事件格式必须为 event|title 或 event|title|subject_type');
        }

        return [$parts[0], $parts[1], $parts[2] ?? null];
    }
}
