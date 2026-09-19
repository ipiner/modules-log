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

it('returns expected subject name column', function () {
    $model = new class extends Model
    {
        use HasOperationLog;

        #[Override]
        public function getTable()
        {
            return 'customs';
        }
    };

    expect($this->invoker($model)->subjectNameColumn())->toBeNull();

    config(['pin.modules.log.operation.subject_name_columns' => ['customs' => 'name']]);
    expect($this->invoker($model)->subjectNameColumn())->toBe('name');
});

it('keeps logging disabled throughout nested callbacks', function () {
    $result = User::withoutOperationLogging(function () {
        User::withoutOperationLogging(fn () => UserFactory::new()->create());
        UserFactory::new()->create();

        return 'callback result';
    });

    expect($result)->toBe('callback result')
        ->and(OperationLog::count())->toBe(0);

    UserFactory::new()->create();
    expect(OperationLog::count())->toBe(1);
});

it('restores the logging state after an exception', function () {
    expect(fn () => User::withoutOperationLogging(fn () => throw new RuntimeException('test')))
        ->toThrow(RuntimeException::class);

    UserFactory::new()->create();
    expect(OperationLog::count())->toBe(1);
});

it('keeps new fields when all supplied old fields are unchanged', function () {
    $user = UserFactory::new()->create();
    $user->mergeOperationChanges(['same' => 'value'], ['same' => 'value', 'added' => 'new']);

    expect($user->operationLog->changes['new']['added'])->toBe('new')
        ->and($user->operationLog->changes['new'])->not()->toHaveKey('same');
});

it('ignores old fields outside the supplied change set', function () {
    $user = UserFactory::new()->create();
    $user->mergeOperationChanges(['not supplied' => 'old'], ['added' => 'new']);

    expect($user->operationLog->changes['new']['added'])->toBe('new')
        ->and($user->operationLog->changes['new'])->not()->toHaveKey('not supplied');
});

it('does not create a standalone log for unchanged values', function () {
    $user = UserFactory::new()->create()->fresh();
    $count = OperationLog::count();
    $user->mergeOperationChanges(['roles' => ['a']], ['roles' => ['a']]);

    expect($user->operationLog)->toBeNull()
        ->and(OperationLog::count())->toBe($count);
});

it('preserves original array values across repeated merges', function () {
    $user = UserFactory::new()->create()->fresh();
    $user->mergeOperationChanges(['roles' => ['a']], ['roles' => ['b']]);
    $user->mergeOperationChanges(['roles' => ['b']], ['roles' => ['c']]);

    expect($user->operationLog->changes)->toBe([
        'old' => ['roles' => ['a']],
        'new' => ['roles' => ['c']],
    ]);

    $user->mergeOperationChanges(['roles' => ['c']], ['roles' => ['a']]);
    expect($user->operationLog->changes)->toBeNull();
});

it('can append changes to a log with null changes', function () {
    $user = UserFactory::new()->create();
    $user->operationLog->update(['changes' => null]);
    $user->mergeOperationChanges(['enabled' => 0], ['enabled' => null]);

    expect($user->operationLog->changes)->toBe([
        'old' => ['enabled' => 0],
        'new' => ['enabled' => null],
    ]);
});

it('respects disabled logging when manually merging changes', function () {
    $user = UserFactory::new()->create()->fresh();
    User::withoutOperationLogging(fn () => $user->mergeOperationChanges([], ['roles' => ['a']]));

    expect($user->operationLog)->toBeNull()
        ->and(OperationLog::count())->toBe(1);
});

it('records only one event for a force deletion', function () {
    $user = UserFactory::new()->create();
    $user->forceDelete();

    expect(OperationLog::where('event', OperationEvent::Deleted->value)->count())->toBe(0)
        ->and(OperationLog::where('event', OperationEvent::ForceDeleted->value)->count())->toBe(1)
        ->and(OperationLog::count())->toBe(2);
});

it('records the persisted values when restoring a model', function () {
    $user = UserFactory::new()->create();
    $user->delete();
    $user = User::withTrashed()->findOrFail($user->id);
    $deletedAt = $user->getRawOriginal('deleted_at');
    $user->restore();

    expect($user->operationLog->event)->toBe(OperationEvent::Restored->value)
        ->and($user->operationLog->changes['old']['deleted_at'])->toBe($deletedAt)
        ->and($user->operationLog->changes['new']['deleted_at'])->toBe(0);
});

it('does not transform unchanged or ignored fields', function () {
    $user = TrackedOperationValuesUser::create(['username' => 'tracked', 'realname' => 'before']);
    $user->transformedKeys = [];
    $user->update(['realname' => 'after']);

    expect($user->transformedKeys)->toBe(['realname', 'realname']);
});

it('does not record timestamp-only updates', function () {
    $user = UserFactory::new()->create();
    $user->update(['updated_at' => now()->addMinute()]);

    expect($user->operationLog)->toBeNull()
        ->and(OperationLog::count())->toBe(1);
});

it('clears the previous operation log when recording fails', function () {
    $user = FailedUpdateLogUser::create(['username' => 'failed', 'realname' => 'before']);
    $user->update(['realname' => 'after']);

    expect($user->operationLog)->toBeNull()
        ->and(OperationLog::count())->toBe(1);
});

it('accepts zero as a subject name', function () {
    $user = new User(['id' => 1, 'username' => '0']);

    expect($this->invoker($user)->subjectName())->toBe('0');
});

class TrackedOperationValuesUser extends User
{
    protected $table = 'users';

    public array $transformedKeys = [];

    protected function transformOperationValue(string $key, mixed $value): mixed
    {
        $this->transformedKeys[] = $key;

        return $value;
    }
}

class FailedUpdateLogUser extends User
{
    protected $table = 'users';

    protected function newOperationPayload(OperationEvent $event, ?array $oldValues, array $newValues): OperationPayload
    {
        if ($event === OperationEvent::Updated) {
            throw new RuntimeException('Log update failed');
        }

        return parent::newOperationPayload($event, $oldValues, $newValues);
    }
}

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
