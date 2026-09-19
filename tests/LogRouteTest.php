<?php

declare(strict_types=1);

use Pin\Modules\Log\Events\OperationEvent;
use Pin\Modules\Log\Facades\Log;
use Pin\Modules\Log\LogRoute;
use Pin\Modules\Log\Payloads\ActivityPayload;
use Pin\Modules\Log\Payloads\LoginPayload;
use Pin\Tests\UserFactory;

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

        LogRoute::LoginLogOption->testJson($this->actingAs($user))
            ->assertJsonFragment(['value' => 0])
            ->assertJsonFragment(['label' => '0/登录成功'])
            ->assertJsonFragment(['value' => 10010])
            ->assertJsonFragment(['label' => '10010/Unknown error']);
    });

    it('sorts login codes numerically', function () {
        $user = UserFactory::new()->create();
        foreach ([10010, 2, 100, 0] as $code) {
            Log::create(new LoginPayload($user, $code));
        }

        $options = LogRoute::LoginLogOption->testJson($this->actingAs($user))->assertOk()->json('data');

        expect(array_column($options, 'value'))->toBe([0, 2, 100, 10010]);
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

        LogRoute::ActivityLogOption->testJson($this->actingAs($user))
            ->assertJsonFragment(['value' => 'create'])
            ->assertJsonFragment(['value' => 'user']);
    });
});
