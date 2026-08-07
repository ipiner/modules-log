<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Pin\Modules\Log\Events\OperationEvent;
use Pin\Modules\Log\Facades\Log;
use Pin\Modules\Log\LogRoute;
use Pin\Modules\Log\LogServiceProvider;
use Pin\Modules\Log\Models\ActivityLog;
use Pin\Modules\Log\Models\LoginLog;
use Pin\Modules\Log\Models\OperationLog;
use Pin\Modules\Log\Payloads\ActivityPayload;
use Pin\Modules\Log\Payloads\LoginPayload;
use Pin\Tests\UserFactory;

beforeEach(function () {
    new LogServiceProvider($this->app)->boot();
});

describe('login logs', function () {
    it('requires authentication', function () {
        LogRoute::LoginLog->testJson($this)->assertStatus(401);
    });

    it(lists('login log'), function () {
        $user = UserFactory::new()->create();
        Log::create(new LoginPayload($user)->context([]));

        LogRoute::LoginLog->testJson($this->actingAs($user))
            ->assertStatus(200)
            ->assertJsonFragment(['username' => $user->username]);
    });

    it('returns login log options', function () {
        $user = UserFactory::new()->create();
        Log::create(new LoginPayload($user));
        Log::create(new LoginPayload($user, 10010));
        Cache::forget(new LoginLog()->getTable().'.options');

        LogRoute::LoginLogOption->testJson($this->actingAs($user))
            ->assertJsonFragment(['value' => 0])
            ->assertJsonFragment(['label' => '0/登录成功'])
            ->assertJsonFragment(['value' => 10010])
            ->assertJsonFragment(['label' => '10010/Unknown error']);
    });
});

describe('operation logs', function () {
    it(lists('operation log'), function () {
        $user = UserFactory::new()->create();

        LogRoute::OperationLog->testJson($this->actingAs($user))
            ->assertStatus(200)
            ->assertJsonFragment(['subject_name' => $user->username]);
    });
    it('returns operation log options', function () {
        $user = UserFactory::new()->create();
        Cache::forget(new OperationLog()->getTable().'.options');

        LogRoute::OperationLogOption->testJson($this->actingAs($user))
            ->assertJsonFragment(['value' => OperationEvent::Created])
            ->assertJsonFragment(['value' => 'user']);
    });
});

describe('activity logs', function () {
    it(lists('activity log'), function () {
        $user = UserFactory::new()->create();
        $payload = new ActivityPayload('create')
            ->context([])
            ->subject(
                $user->id,
                $user->username,
                'user'
            );
        Log::create($payload);

        LogRoute::ActivityLog->testJson($this->actingAs($user))
            ->assertStatus(200)
            ->assertJsonFragment(['subject_name' => $user->username]);
    });
    it('returns activity log options', function () {
        $user = UserFactory::new()->create();
        $payload = new ActivityPayload('create')->subject(
            $user->id,
            $user->username,
            'user'
        );
        Log::create($payload);
        Cache::forget(new ActivityLog()->getTable().'.options');

        LogRoute::ActivityLogOption->testJson($this->actingAs($user))
            ->assertJsonFragment(['value' => 'create'])
            ->assertJsonFragment(['value' => 'user']);
    });
});
