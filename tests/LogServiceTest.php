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

    Cache::forget((new User())->getTable().'.options');

    $options = $this->service->withModel(User::class)->options(
        'username',
        fn ($options) => $options->keyBy('username')->toArray()
    );

    expect($options)->toHaveKey($username);
});
