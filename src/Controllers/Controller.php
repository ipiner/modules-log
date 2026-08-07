<?php

declare(strict_types=1);

namespace Pin\Modules\Log\Controllers;

use Pin\Modules\Log\LogService;

/**
 * 日志基础控制器
 */
abstract class Controller extends \Pin\Http\Controller
{
    public function __construct(protected LogService $service)
    {
        $this->service->withModel($this->modelClass());
    }

    /**
     * 获取日志模型类
     */
    abstract protected function modelClass(): string;
}
