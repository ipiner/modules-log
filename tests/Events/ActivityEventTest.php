<?php

declare(strict_types=1);

use Pin\Tests\Events\Events;

it('gets event', function () {
    expect(Events::PageViewed->event())->toBe('page.viewed');
});

it('gets title', function () {
    expect(Events::PageViewed->title())->toBe('浏览页面{url}');
    expect(Events::PageViewed->title(['url' => 'url']))->toBe('浏览页面url');
});

it('gets subject type', function () {
    expect(Events::PageViewed->subjectType())->toBe('page');
    expect(Events::OrderCreated->subjectType())->toBe('order');
});
