<?php

declare(strict_types=1);

namespace Pin\Modules\Log\Payloads;

use Pin\Models\Model;

/**
 * 登录日志 payload，统一登录成功和失败时写入的字段。
 *
 * @property int $code
 * @property string $message
 */
class LoginPayload extends Payload
{
    /**
     * 根据登录用户和结果信息构造日志载荷。
     */
    public function __construct(?Model $user, int $code = 0, string $message = '登录成功')
    {
        parent::__construct([
            'code' => $code,
            'message' => $message,
        ]);

        if ($user) {
            $this->uid = $user->id;
            $this->username = $user->username;
        }
    }

    /**
     * 输出登录日志表需要的字段。
     */
    public function toArray(): array
    {
        return $this->only(['uid', 'username', 'user_type', 'request_id', 'ip', 'code', 'message', 'context']);
    }
}
