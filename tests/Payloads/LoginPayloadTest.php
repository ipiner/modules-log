<?php

declare(strict_types=1);

use Pin\Modules\Log\Payloads\LoginPayload;
use Pin\Tests\Models\User;

it('only outputs login log fields', function () {
    $user = new User();

    $payload = new LoginPayload($user);

    $payload->set('extra', 'value');

    expect($payload->toArray())
        ->not->toHaveKey('extra')
        ->toHaveKeys([
            'uid',
            'username',
            'code',
            'message',
        ]);
});
