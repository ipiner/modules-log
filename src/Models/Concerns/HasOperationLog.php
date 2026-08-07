<?php

declare(strict_types=1);

namespace Pin\Modules\Log\Models\Concerns;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Pin\Modules\Log\Events\OperationEvent;
use Pin\Modules\Log\Facades\Log;
use Pin\Modules\Log\Models\OperationLog;
use Pin\Modules\Log\Payloads\OperationPayload;
use Pin\Support\Arr;
use Pin\Support\Facades\RuntimeCache;
use Throwable;

/**
 * 模型操作日志
 *
 * 为 Eloquent / Model 提供自动审计日志能力
 */
trait HasOperationLog
{
    /**
     * 最近一次创建的操作日志实例
     */
    public ?OperationLog $operationLog = null;

    /**
     * 绑定模型变更事件到操作日志记录流程
     */
    public static function bootHasOperationLog(): void
    {
        static::created(fn (self $model) => $model->recordOperationLog(OperationEvent::Created));
        static::updated(fn (self $model) => $model->recordOperationLog(OperationEvent::Updated));
        static::deleted(fn (self $model) => $model->recordOperationLog(OperationEvent::Deleted));

        static::registerModelEvent(
            'forceDeleted',
            fn (self $model) => $model->recordOperationLog(OperationEvent::ForceDeleted)
        );

        static::registerModelEvent(
            'restored',
            fn (self $model) => $model->recordOperationLog(OperationEvent::Restored)
        );
    }

    /**
     * 临时禁用日志记录
     */
    public static function withoutOperationLogging(callable $callback): mixed
    {
        $key = static::class.'operation-log-disabled';
        Cache::store('array')->put($key, true);

        try {
            return $callback();
        } finally {
            Cache::store('array')->forget($key);
        }
    }

    /**
     * 合并字段变更到当前日志记录
     *
     * @param  array  $old  旧数据
     * @param  array  $new  新数据
     */
    public function mergeOperationChanges(array $old, array $new): void
    {
        if (! isset($this->operationLog)) {
            $this->operationLog = $this->createOperationLog(OperationEvent::Updated, $old, $new);

            return;
        }
        if ($old) {
            foreach ($old as $key => $value) {
                if ($value == $new[$key]) {
                    unset($old[$key], $new[$key]);
                }
            }
            if (! $old) {
                return;
            }
            $changes = ['old' => $old, 'new' => $new];
        } else {
            $changes = ['new' => $new];
        }

        $this->operationLog->update([
            'changes' => Arr::merge($this->operationLog['changes'], $changes),
        ]);
    }

    /**
     * 创建并持久化操作日志
     *
     * @param  OperationEvent  $event  当前操作事件类型
     * @param  array|null  $oldValues  变更前数据
     * @param  array  $newValues  变更后数据
     * @return OperationLog 已持久化的操作日志模型实例
     */
    protected function createOperationLog(OperationEvent $event, ?array $oldValues, array $newValues)
    {
        return Log::create(
            $this->newOperationPayload($event, $oldValues, $newValues)
        );
    }

    /**
     * 不参与日志记录的字段列表
     */
    protected function ignoredOperationAttributes(): array
    {
        return ['created_at', 'updated_at'];
    }

    /**
     * 判断当前模型是否启用日志记录
     */
    protected function isOperationLoggingEnabled(): bool
    {
        return $this->subjectNameColumn()
            && ! Cache::store('array')->get(static::class.'operation-log-disabled');
    }

    /**
     * 构建 OperationPayload
     *
     * @param  OperationEvent  $event  事件类型
     * @param  array|null  $oldValues  旧数据
     * @param  array  $newValues  新数据
     */
    protected function newOperationPayload(
        OperationEvent $event,
        ?array $oldValues,
        array $newValues,
    ): OperationPayload {
        return new OperationPayload($event)
            ->subject($this->id, $this->subjectName(), $this->subjectType())
            ->changes($oldValues, $newValues, $this->ignoredOperationAttributes());
    }

    /**
     * 记录操作日志
     *
     * @param  OperationEvent  $event  操作事件类型
     */
    protected function recordOperationLog(OperationEvent $event): void
    {
        if (! $this->isOperationLoggingEnabled()) {
            return;
        }

        try {
            $values = $this->resolveOperationChanges($event);
            $this->operationLog = $this->createOperationLog(
                $event,
                $values['old'],
                $values['new']
            );
        } catch (Throwable $e) {
            app('log')->warning($e->getMessage());
        }
    }

    /**
     * 解析模型变更数据
     *
     * @param  OperationEvent  $event  事件类型
     * @return array{old: array|null, new: array}
     */
    protected function resolveOperationChanges(OperationEvent $event): array
    {
        if ($event === OperationEvent::Created) {
            return [
                'old' => null,
                'new' => \Illuminate\Support\Arr::map(
                    $this->getAttributes(),
                    fn (mixed $value, string $key) => $this->transformOperationValue($key, $value),
                ),
            ];
        }

        return [
            'old' => \Illuminate\Support\Arr::map(
                $this->getRawOriginal(),
                fn (mixed $value, string $key) => $this->transformOperationValue($key, $value),
            ),
            'new' => \Illuminate\Support\Arr::map(
                $this->getDirty(),
                fn (mixed $value, string $key) => $this->transformOperationValue($key, $value),
            ),
        ];
    }

    /**
     * 获取操作对象名称
     */
    protected function subjectName(): string
    {
        foreach ((array) $this->subjectNameColumn() as $key) {
            if (! empty($value = $this->getOriginal($key, $this->{$key}))) {
                return (string) $value;
            }
        }

        return (string) $this->id;
    }

    /**
     * 获取操作对象名称的字段
     *
     * 默认从 `modules.log.operation.subject_name_columns.{table}` 读取配置。
     *
     * 未配置时跳过操作日志记录。
     *
     * @return array|string|null 字段名列表
     */
    protected function subjectNameColumn()
    {
        return config(
            'pin.modules.log.operation.subject_name_columns.'.$this->getTable(),
        );
    }

    /**
     * 解析当前模型对应的日志 subject_type
     */
    protected function subjectType(): string
    {
        return RuntimeCache::remember(
            static::class.'operation-log-subject-type',
            fn () => Str::kebab(class_basename(static::class)),
        );
    }

    /**
     * 字段值处理
     *
     * @param  string  $key  字段名
     * @param  mixed  $value  字段值
     * @return mixed 处理后的值
     */
    protected function transformOperationValue(string $key, mixed $value): mixed
    {
        return $value;
    }
}
