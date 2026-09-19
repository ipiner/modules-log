<?php

declare(strict_types=1);

use Pin\Modules\Log\Facades\Log;
use Pin\Modules\Log\Models\LoginLog;
use Pin\Modules\Log\Payloads\LoginPayload;
use Pin\Modules\Log\Payloads\Payload;

it('rejects unsupported payloads with a descriptive exception', function () {
    expect(fn () => Log::create(new Payload()))->toThrow(InvalidArgumentException::class);
});

it('uses the configured log model', function () {
    config(['pin.modules.log.login.model' => CustomLoginLog::class]);

    expect(Log::create(new LoginPayload(null)))->toBeInstanceOf(CustomLoginLog::class);
});

class CustomLoginLog extends LoginLog
{
    protected $table = 'login_logs';
}
