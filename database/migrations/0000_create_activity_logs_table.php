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
        $this->schema()->create('activity_logs', function (Blueprint $table) {
            $this->useTable($table);

            $this->base();
            $this->user();
            $this->subject();
            $this->string('title', '事件标题', null, true);
            $this->json('context', '上下文信息');
            $this->request();

            $table->comment($this->makeComment('行为日志表', 'pin'));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->schema()->dropIfExists('activity_logs');
    }
};
