<?php

declare(strict_types=1);

use Pin\Modules\Log\Events\OperationEvent;

it('returns correct label for created action', function () {
    expect(OperationEvent::labels()['created'])->toBe('添加');
});
