<?php

declare(strict_types=1);

use Pin\Modules\Log\Facades\Log;
use Pin\Modules\Log\Payloads\LoginPayload;
use Pin\Tests\Models\User;
use Pin\Tests\UserFactory;

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

it('does not attribute an unknown login user to the current actor', function () {
    $this->actingAs(UserFactory::new()->create());
    $payload = new LoginPayload(null, 10010, 'unknown user');

    expect($payload->uid)->toBe(0)
        ->and($payload->username)->toBe('');
});

it('normalizes identifiers for users that have not been saved', function () {
    $payload = new LoginPayload(new User());

    expect($payload->uid)->toBe(0)
        ->and($payload->username)->toBe('');
});

it('persists the supplied login timestamp', function () {
    $payload = new LoginPayload(null);
    $payload->created_at = '2025-01-02 03:04:05';
    $log = Log::create($payload)->fresh();

    expect((string) $log->created_at)->toBe('2025-01-02 03:04:05');
});
