<?php

declare(strict_types=1);

namespace Pin\Tests\Events;

use Pin\Modules\Log\Events\ActivityEvent;
use Pin\Modules\Log\Events\IActivityEvent;

enum Events: string implements IActivityEvent
{
    use ActivityEvent;

    case PageViewed = 'page.viewed|浏览页面{url}|page';
    case OrderCreated = 'order.created|创建订单';
}
