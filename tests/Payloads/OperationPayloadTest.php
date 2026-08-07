<?php

declare(strict_types=1);

use Pin\Modules\Log\Events\OperationEvent;
use Pin\Modules\Log\Payloads\OperationPayload;

it('handles operation changes', function () {
    $payload = new OperationPayload(OperationEvent::Created);

    expect(isset($payload->changes))->toBeFalse();

    $payload->changes(['a' => 'a'], []);
    expect($payload->changes)->toBeNull();

    $payload->changes([], ['a' => 'a']);
    expect($payload->changes['old']['a'])->toBeNull()
        ->and($payload->changes['new']['a'])->toBe('a');

    $payload->changes(null, ['a' => 'a']);
    expect(isset($payload->changes['old']['a']))->toBeFalse();

    $payload->changes(null, ['a' => 'a'], ['a']);
    expect($payload->changes)->toBeNull();
});
