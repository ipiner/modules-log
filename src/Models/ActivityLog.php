<?php

declare(strict_types=1);

namespace Pin\Modules\Log\Models;

use Override;
use Pin\Modules\Log\Payloads\ActivityPayload;

/**
 * 行为日志模型
 *
 * @extends Model<ActivityPayload>
 */
class ActivityLog extends Model
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
