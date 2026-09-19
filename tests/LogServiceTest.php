<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Pin\Modules\Log\LogService;
use Pin\Modules\Log\Models\OperationLog;
use Pin\Tests\Models\User;
use Pin\Tests\UserFactory;

pest()->beforeEach(function () {
    $this->service = new LogService(OperationLog::class);
});

it('returns correct activity rules', function () {
    $rules = $this->service->activityRules();

    expect($rules)->toHaveKey('created_at')
        ->and((string) end($rules['subject']))->toBe('q:ns,subject_id,subject_name');
});

it('returns correct options for a model', function () {
    $username = UserFactory::new()->create()->username;
    UserFactory::new()->create();

    $options = $this->service->withModel(User::class)->options(
        'username',
        fn ($options) => $options->keyBy('username')->toArray()
    );

    expect($options)->toHaveKey($username);
});

it('caches mapped options using the table name', function () {
    $user = UserFactory::new()->create();
    $service = $this->service->withModel(User::class);
    $connection = $user->getConnection();
    $connection->enableQueryLog();

    $mappingCalls = 0;
    $map = function ($rows) use (&$mappingCalls) {
        $mappingCalls++;

        return $rows->map(fn ($row) => ['label' => $row->username])->all();
    };
    $first = $service->options('username', $map);
    $second = $service->options('username', $map);

    expect($first)->toBe([['label' => $user->username]])
        ->and($second)->toBe($first)
        ->and(Cache::get($user->getTable().'.options'))->toBe($first)
        ->and($mappingCalls)->toBe(1)
        ->and($connection->getQueryLog())->toHaveCount(1);
});

it('reuses existing cached option results', function () {
    $service = $this->service->withModel(User::class);
    $cached = [['label' => 'existing user', 'value' => 'existing user']];
    Cache::put(new User()->getTable().'.options', $cached, 60);

    expect($service->options('username', fn () => throw new RuntimeException('Should use cached options')))->toBe($cached);
});
