<?php

declare(strict_types=1);

use Workbench\App\Models\User;
use Illuminate\Auth\SessionGuard;

trait SecondaryGuardMountEnvironment
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // A second population, mounted under its own guard, prefix and name prefix. The mount has
        // to exist before the provider boots, because that is when the routes are registered.
        $app['config']->set('auth.guards.admin', ['driver' => 'session', 'provider' => 'users']);
        $app['config']->set('laranail.authkit-preset.guards', [
            'admin' => ['prefix' => 'admin/auth', 'name' => 'admin.'],
        ]);
    }
}

uses(SecondaryGuardMountEnvironment::class);

/**
 * Sign the user in on the named guard *only*, leaving the application default alone.
 *
 * `actingAs($user, 'admin')` cannot be used here. It calls `be()`, which does
 * `$this->app['auth']->shouldUse($guard)` — so the admin guard becomes the *default* and
 * `$request->user()` resolves through it. That hides the very defect these tests exist to catch:
 * the first version of this file used `actingAs` and all four cases passed against the broken code.
 * A real application keeps `web` as its default guard, so the session key is the faithful setup.
 */
function signedInOn(object $test, string $guard, User $user): object
{
    return $test->withSession(['login_' . $guard . '_' . sha1(SessionGuard::class) => $user->getAuthIdentifier()]);
}

// `MultiGuardMountTest` asserts the shape of `AuthPreset::mounts()` and never issues a request, so
// until now nothing drove a secondary mount end to end. These cases close that gap and pin the
// reason a bare `$request->user()` is safe behind `auth:<guard>`: `Illuminate\Auth\Middleware\
// Authenticate::authenticate()` ends with `return $this->auth->shouldUse($guard)`, promoting the
// guard that matched to the application default for the rest of the request. So the handlers that
// call `$request->user()` without naming a guard resolve the right population.
//
// That promotion is framework behaviour this package depends on but does not own. If Laravel ever
// stopped doing it, `PasskeysController` and `UpdateProfileInformationController` would hand a null
// to `->passkeys`, and these tests are what would catch it.

it('renders the passkeys page on a secondary mount', function (): void {
    $admin = User::factory()->create();

    signedInOn($this, 'admin', $admin)
        ->get(route('laranail-auth.admin.user-passkeys.index'))
        ->assertOk();
});

it('renders the profile page on a secondary mount', function (): void {
    $admin = User::factory()->create();

    signedInOn($this, 'admin', $admin)
        ->get(route('laranail-auth.admin.user-profile-information.edit'))
        ->assertOk();
});

it('renders the dashboard on a secondary mount', function (): void {
    $admin = User::factory()->create();

    signedInOn($this, 'admin', $admin)
        ->get(route('laranail-auth.admin.dashboard'))
        ->assertOk();
});

// The primary mount must keep working, so the fix cannot be "always read the admin guard".
it('still renders the passkeys page on the primary mount', function (): void {
    signedInOn($this, 'web', User::factory()->create())
        ->get(route('user-passkeys.index'))
        ->assertOk();
});
