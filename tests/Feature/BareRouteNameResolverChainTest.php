<?php

declare(strict_types=1);

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset;
use Simtabi\Laranail\AuthKit\Preset\Providers\PresetServiceProvider;

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
