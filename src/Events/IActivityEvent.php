<?php

declare(strict_types=1);

namespace Pin\Modules\Log\Events;

/**
 * 行为日志枚举接口
 */
interface IActivityEvent
{
    /**
     * 事件名称
     */
    public function event(): string;

    /**
     * 操作对象类型
     *
     * @return string
     */
    public function subjectType();

    /**
     * 事件标题
     */
    public function title(array $replacement = []): string;
}
