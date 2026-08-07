<?php

declare(strict_types=1);

namespace Pin\Modules\Log\Events;

use Pin\Models\Model;
use Pin\Modules\Log\Models\ActivityLog;
use Pin\Modules\Log\Models\LoginLog;
use Pin\Modules\Log\Models\OperationLog;
use Pin\Modules\Log\Payloads\Payload;

enum LogEvent: string
{
    case Login = 'login';
    case Operation = 'operation';
    case Activity = 'activity';

    /**
     * 获取日志配置
     *
     * @return array{
     *   model: class-string<Model> ,
     *   controller: string,
     *   name: string
     * }
     */
    public function config(): array
    {
        return config('pin.modules.log.'.$this->value);
    }

    /**
     * 创建日志
     *
     * @return ActivityLog|LoginLog|OperationLog
     */
    public function create(array|Payload $data)
    {
        $data = is_array($data) ? $data : $data->toArray();
        $model = $this->config()['model'];

        return $model::create($data);
    }
}
