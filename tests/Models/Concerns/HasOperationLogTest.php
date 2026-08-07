<?php

declare(strict_types=1);

use Pin\Models\Model;
use Pin\Modules\Log\Events\OperationEvent;
use Pin\Modules\Log\Models\Concerns\HasOperationLog;
use Pin\Modules\Log\Models\OperationLog;
use Pin\Modules\Log\Payloads\OperationPayload;
use Pin\Tests\Models\User;
use Pin\Tests\UserFactory;

it('logs all operations', function () {
    $user = User::create(['username' => 'foo', 'realname' => 'foo']);

    expect(OperationLog::where([
        'subject_id' => $user->id,
        'event' => OperationEvent::Created->value,
    ])->count())->toBe(1);

    $user = User::find($user->id);
    $user->update(['password' => 123456, 'realname' => 'bar']);
    expect(OperationLog::where([
        'subject_id' => $user->id,
        'event' => OperationEvent::Updated,
    ])->count())->toBe(1);

    $user = User::find($user->id);
    $user->delete();
    expect(OperationLog::where([
        'subject_id' => $user->id,
        'event' => OperationEvent::Deleted,
    ])->count())->toBe(1);

    $user = User::withTrashed()->find($user->id);
    $user->restore();
    expect(OperationLog::where([
        'subject_id' => $user->id,
        'event' => OperationEvent::Restored,
    ])->count())->toBe(1);

    $user = User::find($user->id);
    $user->forceDelete();
    expect(OperationLog::where([
        'subject_id' => $user->id,
        'event' => OperationEvent::ForceDeleted,
    ])->count())->toBe(1);
});

it('can disable operation logging', function () {
    $user = new User(['username' => 'foo', 'realname' => 'foo']);
    $user->withoutOperationLogging(fn () => $user->save());

    expect(OperationLog::where([
        'subject_id' => $user->id,
        'event' => OperationEvent::Created,
    ])->count())->toBe(0);
});

it('does not create log when custom log throws exception', function () {
    $user = CustomLog::create(['username' => 'foo', 'realname' => 'foo']);

    expect(OperationLog::where([
        'subject_id' => $user->id,
        'event' => OperationEvent::Created,
    ])->count())->toBe(0);
});

it('merges operation changes', function () {
    $user = UserFactory::new()->create();
    $changes = $user->operationLog['changes'];

    expect(isset($changes['new']['username']))->toBeTrue()
        ->and(isset($changes['old']['append']))->toBeFalse()
        ->and(isset($changes['new']['append']))->toBeFalse();

    // same values
    $user->mergeOperationChanges(['append' => 'same'], ['append' => 'same']);
    $changes = $user->operationLog['changes'];
    expect(isset($changes['old']['append']))->toBeFalse()
        ->and(isset($changes['new']['append']))->toBeFalse();

    // append changes
    $user->mergeOperationChanges(['append' => 'a'], ['append' => 'b']);
    $changes = $user->operationLog['changes'];
    expect(isset($changes['new']['append']))->toBeTrue()
        ->and(isset($changes['old']['append']))->toBeTrue();

    $user->operationLog = null;
    $user->mergeOperationChanges(['append' => 'c'], ['append' => 'b']);
    $changes = $user->operationLog['changes'];
    expect(isset($changes['new']['username']))->toBeFalse()
        ->and(isset($changes['old']['append']))->toBeTrue();

    $user->mergeOperationChanges([], ['append_new' => 'b']);
    $changes = $user->operationLog['changes'];
    expect(isset($changes['new']['append_new']))->toBeTrue()
        ->and(isset($changes['old']['append_new']))->toBeFalse();
});

it('returns correct subject name', function () {
    $user = new User(['username' => 'foo', 'realname' => 'foo']);
    expect($this->invoker($user)->subjectName())->toBe('foo');

    $user->name = 'name';
    expect($this->invoker($user)->subjectName())->toBe('name');

    expect($this->invoker(new User(['id' => 1]))->subjectName())->toBe('1');
});

it('returns expexted subject name column', function () {
    $o = new class extends Model
    {
        use HasOperationLog;

        #[Override]
        public function getTable()
        {
            return 'customs';
        }
    };

    expect($this->invoker($o)->subjectNameColumn())->toBeNull();

    config(['pin.modules.log.operation.subject_name_columns' => ['customs' => 'name']]);
    expect($this->invoker($o)->subjectNameColumn())->toBe('name');
});

class CustomLog extends User
{
    protected $table = 'users';

    public function newOperationPayload(
        OperationEvent $event,
        ?array $oldValues,
        array $newValues,
    ): OperationPayload {
        throw new RuntimeException('Exception test');
    }
}
