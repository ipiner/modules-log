<?php

declare(strict_types=1);

use Pin\Modules\Log\Events\ActivityEvent;
use Pin\Modules\Log\Events\IActivityEvent;
use Pin\Tests\Events\Events;

it('gets event', function () {
    expect(Events::PageViewed->event())->toBe('page.viewed');
});

it('rejects malformed activity event definitions', function (MalformedActivityEvent $event) {
    expect(fn () => $event->title())->toThrow(InvalidArgumentException::class);
})->with(fn () => MalformedActivityEvent::cases());

enum MalformedActivityEvent: string implements IActivityEvent
{
    use ActivityEvent;

    case MissingTitle = 'missing.title';
    case EmptyEvent = '|title';
    case ExtraParts = 'event|title|subject|extra';
}

it('gets title', function () {
    expect(Events::PageViewed->title())->toBe('浏览页面{url}');
    expect(Events::PageViewed->title(['url' => 'url']))->toBe('浏览页面url');
});

it('gets subject type', function () {
    expect(Events::PageViewed->subjectType())->toBe('page');
    expect(Events::OrderCreated->subjectType())->toBe('order');
});
