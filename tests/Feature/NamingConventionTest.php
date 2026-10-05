<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Console\Kernel;
use Simtabi\Laranail\Package\Tools\Testing\NamingScope;
use Simtabi\Laranail\Package\Tools\Testing\AssertsRegisteredNames;

uses(AssertsRegisteredNames::class);

/**
 * Every public name this package registers lands in a flat, global registry. A second package
 * claiming the same key does not collide loudly — it silently replaces the first, and the damage
 * surfaces far away as a missing view or the wrong middleware.
 *
 * These assertions read the LIVE registries on a booted application rather than grepping the
 * provider, so they keep their teeth through any refactor of the registration code.
 */
it('registers its view namespace as the composer package name', function (): void {
    expect(View::getFinder()->getHints())->toHaveKey('laranail/authkit-preset')
        ->and(View::getFinder()->getHints())->not->toHaveKey('auth-preset');
});

it('aliases the hyphen form over the same paths, because Blade tags cannot hold a slash', function (): void {
    // Blade's component-tag pattern is x[-\:]([\w\-\:\.]*) -- no forward slash -- so
    // <x-laranail/authkit-preset::layout /> truncates at the slash and is never compiled. The alias
    // must resolve the *same* paths, or a published override would be found under one spelling and
    // not the other.
    $hints = View::getFinder()->getHints();

    expect($hints)->toHaveKey('laranail-authkit-preset')
        ->and($hints['laranail-authkit-preset'])->toBe($hints['laranail/authkit-preset']);
});

it('registers its translation namespace under the same prefix as its views', function (): void {
    // Laravel resolves published overrides from lang/vendor/{namespace}; a mismatch between the
    // namespace and the publish destination is silent, and the packaged default keeps answering.
    expect(Lang::getLoader()->namespaces())->toHaveKey('laranail/authkit-preset');
});

it('resolves its translation keys', function (): void {
    expect(__('laranail/authkit-preset::messages.login.title'))->toBe('Sign in to your account')
        ->and(__('laranail/authkit-preset::messages.dashboard.title'))->toBe('Dashboard');
});

it('names its Artisan command laranail::<slug>.<command>', function (): void {
    $names = array_keys(app(Kernel::class)->all());

    expect($names)->toContain('laranail::authkit-preset.install')
        ->and($names)->not->toContain('laranail:authkit.install');
});

it('does not register a bare short alias for its command', function (): void {
    // An alias such as `authkit:install` hands back exactly the collision the namespaced name
    // exists to prevent, so the convention forbids it.
    $bare = array_filter(
        array_keys(app(Kernel::class)->all()),
        fn (string $n): bool => str_contains($n, 'authkit') && ! str_starts_with($n, 'laranail::'),
    );

    expect(array_values($bare))->toBe([]);
});

it('never registers a bare publish tag', function (): void {
    // Testbench does not always populate publishableGroups(), so this asserts the invariant that
    // matters — nothing unscoped. The positive case is proved end-to-end in the demo application.
    $bare = array_filter(
        array_keys(ServiceProvider::publishableGroups()),
        fn (string $tag): bool => str_contains($tag, 'authkit') && ! str_starts_with($tag, 'laranail::'),
    );

    expect(array_values($bare))->toBe([]);
});

it('keeps its configuration under the laranail namespace', function (): void {
    expect(config('laranail.authkit-preset'))->toBeArray()
        ->and(config('auth-preset'))->toBeNull();
});

it('registers its Blade components under laranail-authkit-preset::, keeping only the deprecated alias bare', function (): void {
    // Reads Blade's live alias, class-namespace and anonymous registries. The old
    // `authkit-social-buttons` tag stays registered so published views keep compiling; it is the
    // one bare name allowed, and listing it here makes a stale entry fail rather than pass.
    $found = $this->assertBladeComponentsScoped(
        // basePath is src/: package-tools v0.1.3 defaults ownership to the package root, which
        // counts vendor/ and tests/ closures as this package's (fixed in v0.1.4).
        NamingScope::for('laranail/authkit-preset', 'Simtabi\\Laranail\\AuthKit\\Preset\\', basePath: dirname(__DIR__, 2) . '/src'),
        deprecated: ['authkit-social-buttons'],
    );

    expect($found)->toContain('laranail-authkit-preset::social-buttons');
});

it('keeps the deprecated bare route names resolving to the scoped routes', function (): void {
    $this->assertDeprecatedRouteNamesResolve([
        'login'            => 'laranail-auth.login',
        'password.request' => 'laranail-auth.password.request',
        'api.login'        => 'laranail-auth-api.login',
    ]);
});
