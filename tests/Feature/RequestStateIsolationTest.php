<?php

declare(strict_types=1);

use Illuminate\Routing\Route;
use Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset;
use Simtabi\Laranail\Package\Tools\Testing\AssertsNoStateLeak;

uses(AssertsNoStateLeak::class);

it('resolves the active route mount again for each request in one container', function (): void {
    $this->assertNoStateLeaksBetweenRequests(function (string $marker): string {
        config()->set('laranail.authkit-preset.guards', [
            $marker => ['prefix' => 'staff/auth', 'name' => $marker . '.'],
        ]);
        $route = new Route('GET', 'staff/auth/login', fn () => 'Login');
        $route->name('laranail-auth.' . $marker . '.login');
        $this->app['request']->setRouteResolver(fn () => $route);

        return AuthPreset::guardForCurrentRoute();
    });
});
