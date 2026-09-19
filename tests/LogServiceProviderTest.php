<?php

declare(strict_types=1);

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Pin\Modules\Log\Log;
use Pin\Modules\Log\LogServiceProvider;

it('registers configuration and services before booting', function () {
    $application = new Application(dirname(__DIR__));
    $application->instance('config', new Repository([
        'pin' => ['modules' => ['log' => ['routes' => ['prefix' => '/custom/log']]]],
    ]));

    new LogServiceProvider($application)->register();

    expect($application['config']->get('pin.modules.log.routes.prefix'))->toBe('/custom/log')
        ->and($application['config']->get('pin.modules.log.login.route_enabled'))->toBeTrue()
        ->and($application->make(Log::class))->toBe($application->make('pin.modules.log'));
});

it('uses the same instance for class injection and the legacy service name', function () {
    expect(app(Log::class))->toBe(app('pin.modules.log'));
});
