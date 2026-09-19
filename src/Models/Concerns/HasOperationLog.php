<?php

declare(strict_types=1);

namespace Pin\Modules\Log\Models\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Pin\Modules\Log\Events\OperationEvent;
use Pin\Modules\Log\Facades\Log;
use Pin\Modules\Log\Models\OperationLog;
use Pin\Modules\Log\Payloads\OperationPayload;
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
        static::created(static fn (self $model) => $model->recordOperationLog(OperationEvent::Created));
        static::updated(static fn (self $model) => $model->recordOperationLog(OperationEvent::Updated));
        static::deleted(static function (self $model): void {
            // 软删除模型的强制删除还会触发 forceDeleted，由该事件单独记录。
            if (! method_exists($model, 'isForceDeleting') || ! $model->isForceDeleting()) {
                $model->recordOperationLog(OperationEvent::Deleted);
            }
        });

        static::registerModelEvent(
            'forceDeleted',
            static fn (self $model) => $model->recordOperationLog(OperationEvent::ForceDeleted)
        );

        static::registerModelEvent(
            'restored',
            static fn (self $model) => $model->recordOperationLog(OperationEvent::Restored)
        );
    }

    /**
     * 临时禁用当前模型类的日志，嵌套调用和异常退出时恢复进入前的状态。
     */
    public static function withoutOperationLogging(callable $callback): mixed
    {
        $key = static::class.'operation-log-disabled';
        $cache = Cache::store('array');
        $previous = $cache->get($key);
        $cache->put($key, true);

        try {
            return $callback();
        } finally {
            if ($previous === null) {
                $cache->forget($key);
            } else {
                $cache->put($key, $previous);
            }
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
        if (! $this->isOperationLoggingEnabled()) {
            return;
        }

        $new = array_diff_key($new, array_fill_keys($this->ignoredOperationAttributes(), true));
        foreach ($new as $key => $value) {
            if (array_key_exists($key, $old) && $old[$key] === $value) {
                unset($new[$key]);
            }
        }

        if ($new === []) {
            return;
        }

        $old = array_intersect_key($old, $new);
        if ($this->operationLog === null) {
            $this->operationLog = $this->createOperationLog(OperationEvent::Updated, $old ?: null, $new);

            return;
        }

        // 显式读取模型字段，避免访问到 Eloquent 同名的 protected $changes 属性。
        $changes = $this->operationLog->getAttribute('changes') ?? [];
        foreach ($new as $key => $value) {
            // 已记录字段保留最初的旧值；数组字段整体替换，避免递归合并污染快照。
            if (! array_key_exists($key, $changes['new'] ?? []) && array_key_exists($key, $old)) {
                $changes['old'][$key] = $old[$key];
            }

            $changes['new'][$key] = $value;
            if (array_key_exists($key, $changes['old'] ?? []) && $changes['old'][$key] === $value) {
                unset($changes['old'][$key], $changes['new'][$key]);
            }
        }

        if (empty($changes['old'])) {
            unset($changes['old']);
        }

        $this->operationLog->update(['changes' => empty($changes['new']) ? null : $changes]);
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
        $this->operationLog = null;

        try {
            if (! $this->isOperationLoggingEnabled()) {
                return;
            }

            $values = $this->resolveOperationChanges($event);
            if ($event === OperationEvent::Updated && $values['new'] === []) {
                return;
            }

            $this->operationLog = $this->createOperationLog(
                $event,
                $values['old'],
                $values['new']
            );
        } catch (Throwable $exception) {
            app('log')->warning($exception->getMessage(), [
                'exception' => $exception,
                'model' => static::class,
                'subject_id' => $this->getKey(),
                'event' => $event->value,
            ]);
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
        $oldValues = match ($event) {
            OperationEvent::Created => null,
            OperationEvent::Restored => $this->getPrevious(),
            default => $this->getRawOriginal(),
        };
        $newValues = match ($event) {
            OperationEvent::Created => $this->getAttributes(),
            // updated/restored 读取实际保存的字段，避免记录事件回调中尚未保存的数据。
            OperationEvent::Updated, OperationEvent::Restored => $this->getChanges(),
            default => $this->getDirty(),
        };
        $newValues = array_diff_key($newValues, array_fill_keys($this->ignoredOperationAttributes(), true));
        $oldValues = $oldValues === null ? null : array_intersect_key($oldValues, $newValues);

        return [
            'old' => $oldValues === null ? null : Arr::map(
                $oldValues,
                fn (mixed $value, string $key) => $this->transformOperationValue($key, $value),
            ),
            'new' => Arr::map(
                $newValues,
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
            $value = $this->getOriginal($key, $this->getAttribute($key));
            if ($value !== null && $value !== '') {
                return (string) $value;
            }
        }

        return (string) $this->id;
    }

    /**
     * 获取操作对象名称的字段
     *
     * 默认从 `pin.modules.log.operation.subject_name_columns.{table}` 读取配置。
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
