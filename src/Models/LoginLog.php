<?php

declare(strict_types=1);

namespace Pin\Modules\Log\Models;

use Pin\Modules\Log\Payloads\LoginPayload;

/**
 * @extends Model<LoginPayload>
 */
class LoginLog extends Model
{
    protected $casts = [
        'context' => 'array',
        'code' => 'integer',
    ];
}
