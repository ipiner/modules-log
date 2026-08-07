<?php

declare(strict_types=1);

namespace Pin\Modules\Log\Models;

use Override;
use Pin\Modules\Log\Payloads\LoginPayload;

/**
 * @extends Model<LoginPayload>
 */
class LoginLog extends Model
{
    #[Override]
    protected function onCreating()
    {
        if ($this->context === []) {
            $this->context = null;
        }
        parent::onCreating();
    }
}
