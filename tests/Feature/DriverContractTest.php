<?php

declare(strict_types=1);

use Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset;
use Simtabi\Laranail\AuthKit\Preset\Enums\FrontendStack;

it('ships working login views for every advertised frontend stack', function (FrontendStack $stack): void {
    config()->set('laranail.authkit-preset.stack', $stack->value);

    expect(AuthPreset::stack())->toBe($stack);
    expect(view()->exists(AuthPreset::view('login')))->toBeTrue();
    expect(view()->exists(AuthPreset::view('register')))->toBeTrue();
})->with(FrontendStack::cases());
