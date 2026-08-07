<?php

declare(strict_types=1);

namespace Pin\Modules\Log\Models;

class Model extends \Pin\Models\Model
{
    /**
     * 禁用 Eloquent 自动时间戳
     */
    public $timestamps = false;

    protected $casts = [
        'context' => 'array',
    ];
}
