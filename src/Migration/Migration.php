<?php

declare(strict_types=1);

namespace Pin\Modules\Log\Migration;

use Pin\Modules\Log\Migration\Concerns\HasColumns;

/**
 * 日志表迁移
 */
class Migration extends \Pin\Database\Migration
{
    use HasColumns;
}
