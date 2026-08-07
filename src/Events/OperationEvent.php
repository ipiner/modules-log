<?php

declare(strict_types=1);

namespace Pin\Modules\Log\Events;

/**
 * 操作日志枚举
 */
enum OperationEvent: string
{
    /**
     * 创建操作
     */
    case Created = 'created';

    /**
     * 更新操作
     */
    case Updated = 'updated';

    /**
     * 删除操作（软删除或逻辑删除）
     */
    case Deleted = 'deleted';

    /**
     * 强制删除（物理删除）
     */
    case ForceDeleted = 'force-deleted';

    /**
     * 恢复（从软删除恢复）
     */
    case Restored = 'restored';

    /**
     * 获取所有操作事件的中文描述
     *
     * @return array<string,string>
     */
    public static function labels(): array
    {
        return [
            self::Created->value => '添加',
            self::Updated->value => '更新',
            self::Deleted->value => '删除',
            self::ForceDeleted->value => '强制删除',
            self::Restored->value => '恢复',
        ];
    }
}
