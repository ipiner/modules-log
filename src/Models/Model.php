<?php

declare(strict_types=1);

namespace Pin\Modules\Log\Models;

use Override;

/**
 * 日志模型基类，统一上下文字段的存储方式。
 */
class Model extends \Pin\Models\Model
{
    /**
     * 禁用 Eloquent 自动时间戳
     */
    public $timestamps = false;

    protected $casts = [
        'context' => 'array',
    ];

    #[Override]
    protected function onCreating()
    {
        if ($this->context === []) {
            $this->context = null;
        }

        parent::onCreating();
    }
}
