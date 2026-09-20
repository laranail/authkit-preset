<?php

declare(strict_types=1);

use Simtabi\Laranail\AuthKit\Preset\Commands\InstallCommand;
use Simtabi\Laranail\Package\Tools\Testing\AssertsDriverContract;

uses(AssertsDriverContract::class);

/**
 * `--stack=` arrives as `''`, not `null`, so the `??` that was meant to fall
 * through to the interactive picker did not fire. The install then compared
 * `'' !== 'blade'` and failed with "Only the [blade] stack is currently
 * supported" -- a message about a stack the caller never named.
 */
it('falls through to the stack prompt when --stack is written without a value', function (): void {
    $source = (string) file_get_contents((string) (new ReflectionClass(InstallCommand::class))->getFileName());

    expect($source)->not->toContain("\$this->option(key: 'stack') ??")
        ->and($source)->toContain("\$this->strOption('stack') ??");
});

it('has no console option defaulted by a null-only test', function (): void {
    $this->assertNoNullOnlyOptionGuards(__DIR__ . '/../../src');
});
