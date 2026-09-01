<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset;

/*
|--------------------------------------------------------------------------
| Web routes, mounted once per user population
|--------------------------------------------------------------------------
|
| An application with more than one kind of user -- customers on the `web` guard, staff on an
| `admin` guard -- needs the same authentication routes for each, separated by URL and by route
| name. Previously the guard was a scalar and the group was registered once, so a second
| population had nowhere to mount: the actions already take a $guard and are guard-agnostic, and
| only this wiring was single-guard.
|
| Each mount requires the same file rather than duplicating it, so a route added for one
| population cannot be forgotten for the others.
|
*/

foreach (AuthPreset::mounts() as $mount) {
    $prefix = $mount['prefix'];
    $guard = $mount['guard'];
    $isPrimaryMount = $mount['primary'];

    // Positional, not named: Route::name() resolves through RouteRegistrar::__call, which reads
    // $parameters[0]. A named argument leaves that slot empty and the value degrades to true, so
    // every route silently gained a "1" name prefix -- route('login') became route('1login').
    Route::name($mount['name'])->group(function () use ($prefix, $guard, $isPrimaryMount): void {
        require __DIR__.'/web-mount.php';
    });
}
