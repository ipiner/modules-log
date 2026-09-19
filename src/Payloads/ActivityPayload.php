<?php

declare(strict_types=1);

namespace Pin\Modules\Log\Payloads;

use BackedEnum;
use Pin\Modules\Log\Events\IActivityEvent;
use Pin\Support\Str;

/**
 * 行为日志 Payload
 *
 * @property string $event 操作事件标识
 * @property int $subject_id 操作对象id
 * @property string $subject_name 操作对象名称
 * @property string $subject_type 操作对象类型
 * @property string $title 事件标题
 *
 * @method $this title(string $title)
 */
class ActivityPayload extends Payload
{
    public function __construct(string|BackedEnum|IActivityEvent $event, array $attributes = [])
    {
        $defaults = ['subject_id' => 0, 'subject_name' => '', 'subject_type' => ''];
        if ($event instanceof IActivityEvent) {
            $eventName = $event->event();
            $defaults['title'] = $event->title();
            $defaults['subject_type'] = $event->subjectType();
        } else {
            $eventName = Str::string($event);
        }

        // 调用方提供的对象和标题覆盖默认值，事件标识始终由 $event 决定。
        parent::__construct(array_replace($defaults, $attributes, ['event' => $eventName]));
    }

    /**
     * 设置操作对象（Subject）
     *
     * @param  int|null  $id  对象ID
     * @param  string  $name  对象名称
     * @param  string|null  $type  对象类型
     */
    public function subject(?int $id, string $name, ?string $type = null): static
    {
        $this->subject_id = $id ?? 0;
        $this->subject_name = $name;

        if ($type !== null) {
            $this->subject_type = $type;
        }

        return $this;
    }
}
