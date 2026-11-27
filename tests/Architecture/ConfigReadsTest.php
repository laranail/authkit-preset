<?php

declare(strict_types=1);

use Simtabi\Laranail\Package\Tools\Testing\AssertsDriverContract;

uses(AssertsDriverContract::class);

it('reads package configuration only at its registered key', function (): void {
    $this->assertReadsConfigAtRegisteredKey(dirname(__DIR__, 2) . '/src', 'authkit-preset');
    $this->assertReadsConfigAtRegisteredKey(dirname(__DIR__, 2) . '/routes', 'authkit-preset');
});
