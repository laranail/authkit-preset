<?php

declare(strict_types=1);

use Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset;

it('mounts once for the primary guard, keeping the bare route names', function (): void {
    // The bare names are load-bearing: Laravel's own guest redirect calls route('login') and the
    // verified middleware resolves verification.notice, so the primary mount must not prefix them.
    expect(AuthPreset::mounts())->toBe([
        ['guard' => 'web', 'prefix' => 'auth', 'name' => ''],
    ]);
});

it('adds a mount per configured user population', function (): void {
    config()->set('laranail.authkit-preset.guards', [
        'admin' => ['prefix' => 'admin/auth', 'name' => 'admin.'],
    ]);

    expect(AuthPreset::mounts())->toBe([
        ['guard' => 'web',   'prefix' => 'auth',       'name' => ''],
        ['guard' => 'admin', 'prefix' => 'admin/auth', 'name' => 'admin.'],
    ]);
});

it('derives a prefix and name prefix when none are given', function (): void {
    config()->set('laranail.authkit-preset.guards', ['admin' => []]);

    expect(AuthPreset::mounts()[1])
        ->toBe(['guard' => 'admin', 'prefix' => 'admin/auth', 'name' => 'admin.']);
});

it('never mounts the primary guard twice', function (): void {
    // Listing the primary guard again would register every route a second time under the same
    // names, and the second registration silently wins.
    config()->set('laranail.authkit-preset.guards', ['web' => ['prefix' => 'other']]);

    expect(AuthPreset::mounts())->toHaveCount(1);
});
