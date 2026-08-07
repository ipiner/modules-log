<?php

declare(strict_types=1);

namespace Pin\Modules\Log\Migration\Concerns;

/**
 * HasColumns
 *
 * 日志表迁移
 */
trait HasColumns
{
    /**
     * 基础日志字段
     */
    protected function base(): void
    {
        $this->id();
        $this->string('event', '事件', 30)->index();
        $this->timestamp('created_at', '日志时间')->index();
    }

    /**
     * 用户字段
     */
    protected function user(): void
    {
        $this->unsignedBigInteger('uid', '用户id');
        $this->string('username', '用户名', 30);
        $this->string('user_type', '用户类型', 30);
    }

    /**
     * 操作对象字段
     */
    protected function subject(): void
    {
        $this->string('subject_type', '操作对象类型', 120, true);
        $this->unsignedBigInteger('subject_id', '操作对象id')->default(0);
        $this->string('subject_name', '操作对象名称', 500, true);

        $this->table->index(['subject_type', 'subject_id']);
    }

    /**
     * HTTP 请求上下文信息
     */
    protected function request(): void
    {
        $this->string('request_id', '请求id', 36)->index();
        $this->string('request_method', 'HTTP请求方法，命令行下为console', 10);
        $this->string('request_url', '请求URL，命令行下为命令完整参数', 500);
        $this->string('route', '路由名称或者路由URI');
        $this->string('ip', '客户端IP地址', 45);
    }
}
