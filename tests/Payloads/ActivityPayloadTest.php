<?php

declare(strict_types=1);

use Pin\Modules\Log\Events\OperationEvent;
use Pin\Modules\Log\Payloads\ActivityPayload;
use Pin\Tests\Events\Events;

it('has empty subject by default and can set subject values', function () {
    $payload = new ActivityPayload(OperationEvent::Created);

    expect($payload->subject_type)->toBe('')
        ->and($payload->subject_id)->toBe(0)
        ->and($payload->subject_name)->toBe('');

    $payload->subject(1, 'name', 'table');
    expect($payload->subject_type)->toBe('table')
        ->and($payload->subject_id)->toBe(1)
        ->and($payload->subject_name)->toBe('name');

    $payload = new ActivityPayload(Events::OrderCreated);
    expect($payload->event)->toBe('order.created')
        ->and($payload->subject_type)->toBe('order')
        ->and($payload->title)->toBe('创建订单');
});

it('preserves explicitly supplied subject and title attributes', function () {
    $payload = new ActivityPayload(Events::OrderCreated, [
        'subject_id' => 123,
        'subject_name' => 'order 123',
        'subject_type' => 'custom order',
        'title' => 'custom title',
        'event' => 'ignored',
    ]);

    expect($payload)->subject_id->toBe(123)
        ->subject_name->toBe('order 123')
        ->subject_type->toBe('custom order')
        ->title->toBe('custom title')
        ->event->toBe('order.created');
});
