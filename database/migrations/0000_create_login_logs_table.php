<?php

use Illuminate\Database\Schema\Blueprint;
use Pin\Modules\Log\Migration\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->schema()->create('login_logs', function (Blueprint $table) {
            $this->useTable($table);
            $this->id();

            $this->user();
            $this->string('ip', '登录ip', 45);
            $this->unsignedInteger('code', '登录返回码')->default(0);
            $this->string('message', '登录失败时的提示信息', null, true);
            $this->json('context', '上下文信息');
            $this->requestId();
            $this->timestamp('created_at', '创建时间')->useCurrent()->index();

            $table->comment($this->makeComment('登录日志表', 'pin'));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->schema()->dropIfExists('login_logs');
    }
};
