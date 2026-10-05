<?php

declare(strict_types=1);

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\AuthKit\Support\AuthKit;
use Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset;
use Simtabi\Laranail\AuthKit\Preset\Providers\PresetServiceProvider;
use Simtabi\Laranail\Package\Tools\Support\Routing\BareRouteNameAliases;

/*
 * The URL generator holds exactly one missing-route resolver, so installing one replaces whatever
 * a sibling package (error-pages, env-kit-webui, ...) installed earlier. This package has to chain
 * to that resolver rather than discard it.
 */
it(description: 'keeps a previously installed missing-route resolver working', closure: function (): void {
    Route::get('/sibling-target', fn (): string => 'ok')->name('sibling.scoped');
    Route::getRoutes()->refreshNameLookups();

    URL::resolveMissingNamedRoutesUsing(
        fn (string $name, mixed $parameters, ?bool $absolute): ?string => $name === 'sibling.bare'
            ? URL::route('sibling.scoped', $parameters ?? [], $absolute ?? true)
            : null,
    );

    // Reinstall this package's resolver, as if it booted after the sibling.
    $provider = app()->getProvider(PresetServiceProvider::class);
    Closure::bind(fn () => $this->resolveBareRouteNames(), $provider, PresetServiceProvider::class)();

    expect(route('sibling.bare'))->toBe(url('/sibling-target'))
        ->and(route('login'))->toBe(route(AuthPreset::routeName('login')));
});

it(description: 'still throws for a name nobody resolves', closure: function (): void {
    URL::resolveMissingNamedRoutesUsing(fn (): ?string => null);

    $provider = app()->getProvider(PresetServiceProvider::class);
    Closure::bind(fn () => $this->resolveBareRouteNames(), $provider, PresetServiceProvider::class)();

    route('no-such-route-anywhere');
})->throws(Symfony\Component\Routing\Exception\RouteNotFoundException::class);

it(description: 'resolves its own bare names when no resolver was installed before it', closure: function (): void {
    Closure::bind(fn () => $this->missingNamedRouteResolver = null, URL::getFacadeRoot(), Illuminate\Routing\UrlGenerator::class)();

    $provider = app()->getProvider(PresetServiceProvider::class);
    Closure::bind(fn () => $this->resolveBareRouteNames(), $provider, PresetServiceProvider::class)();

    expect(route('login'))->toBe(route(AuthPreset::routeName('login')));
});

/*
 * The hand-rolled resolver was replaced by package-tools' shared BareRouteNameAliases. These pin
 * that the shared one is what is installed, and that it keeps every behaviour the old one had:
 * the API's old `api.*` names, silence for the framework's own bare names, and an empty prefix.
 */
it(description: 'installs the shared package-tools resolver', closure: function (): void {
    expect(BareRouteNameAliases::previousResolver(URL::getFacadeRoot()))
        ->toBeInstanceOf(BareRouteNameAliases::class);
});

it(description: 'resolves the old api.* names to the API prefix', closure: function (): void {
    expect(Route::has('api.login'))->toBeFalse()
        ->and(route('api.login'))->toBe(route(AuthKit::apiRouteNamePrefix() . 'login'));
});

it(description: 'raises no deprecation for the bare names the framework reads back', closure: function (): void {
    BareRouteNameAliases::forgetWarnings();
    $raised = [];
    set_error_handler(function (int $errno, string $message) use (&$raised): bool {
        $raised[] = $message;

        return true;
    }, E_USER_DEPRECATED);

    try {
        route('login');
        route('password.request');
        route('api.login');
    } finally {
        restore_error_handler();
    }

    expect($raised)->toBe([]);
});

it(description: 'installs nothing for a prefix configured empty', closure: function (): void {
    config()->set('laranail.authkit-preset.route_name_prefix', '');
    config()->set('laranail.authkit.api.name_prefix', '');
    URL::resolveMissingNamedRoutesUsing(fn (): ?string => null);

    $provider = app()->getProvider(PresetServiceProvider::class);
    Closure::bind(fn () => $this->resolveBareRouteNames(), $provider, PresetServiceProvider::class)();

    expect(BareRouteNameAliases::previousResolver(URL::getFacadeRoot()))
        ->not->toBeInstanceOf(BareRouteNameAliases::class);
});
