<?php

declare(strict_types=1);

namespace Pin\Modules\Log\Payloads;

use Pin\Modules\Log\Events\OperationEvent;

/**
 * 数据变更日志 Payload
 *
 * @property ?array $changes 数据变更记录（old/new结构）
 */
class OperationPayload extends ActivityPayload
{
    public function __construct(string|OperationEvent $event, array $attributes = [])
    {
        parent::__construct($event, $attributes);
    }

    /**
     * 计算并记录字段变更内容
     *
     * @param  array|null  $oldValues  变更前数据
     * @param  array  $newValues  变更后数据
     * @param  array  $ignores  忽略字段列表
     */
    public function changes(?array $oldValues, array $newValues, array $ignores = []): static
    {
        $result = [];

        foreach ($newValues as $key => $value) {
            if (in_array($key, $ignores)) {
                continue;
            }

            if ($oldValues !== null) {
                $result['old'][$key] = $oldValues[$key] ?? null;
            }

            $result['new'][$key] = $value;
        }

        $this->changes = $result ?: null;

        return $this;
    }
}
