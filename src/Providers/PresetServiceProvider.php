<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AuthKit\Preset\Providers;

use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;
use Simtabi\Laranail\AuthKit\Support\AuthKit;
use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\AuthKit\Preset\Support;
use Simtabi\Laranail\AuthKit\Preset\Features;
use Simtabi\Laranail\AuthKit\Preset\Commands\InstallCommand;
use Simtabi\Laranail\AuthKit\Preset\Http\Middleware\ValidateCaptcha;
use Simtabi\Laranail\AuthKit\Preset\Http\Middleware\PreventAuthenticatedPageCaching;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;

class PresetServiceProvider extends PackageServiceProvider
{
    /** The canonical namespace: the composer package name, so a reader can trace a key to a package. */
    public const string VIEW_NAMESPACE = 'laranail/authkit-preset';

    public const string TRANSLATION_NAMESPACE = 'laranail/authkit-preset';

    /** Blade component tags cannot contain a forward slash; this alias resolves the same paths. */
    public const string COMPONENT_NAMESPACE = 'laranail-authkit-preset';

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laranail/authkit-preset')
            ->publish(
                ['config/laranail/authkit-preset.php' => config_path('laranail/authkit-preset.php')],
                'laranail::authkit-preset-config',
            )
            ->publish(
                ['routes/web.php' => base_path('routes/laranail-authkit-preset-web.php')],
                'laranail::authkit-preset-routes',
            )
            ->publish(
                ['routes/api.php' => base_path('routes/laranail-authkit-preset-api.php')],
                'laranail::authkit-preset-routes',
            )
            ->publish(
                ['resources/views/blade' => resource_path('views/vendor/laranail/authkit-preset')],
                'laranail::authkit-preset-views',
            )
            ->publish(
                ['lang' => lang_path('vendor/laranail/authkit-preset')],
                'laranail::authkit-preset-lang',
            );
    }

    public function packageRegistered(): void
    {
        $this->mergeConfigFrom($this->packagePath('config/laranail/authkit-preset.php'), 'laranail.authkit-preset');

        // Fortify registers its own POST endpoints at the application root -- /login, /logout,
        // /forgot-password, /reset-password, /user/* -- in parallel with the ones this package
        // mounts under its configured prefix. That is not cosmetic duplication:
        //
        //  - Route names are a flat global registry, so login.store, logout, password.email and
        //    password.update were each claimed twice and route() resolved to whichever won.
        //  - The root copies carried neither the captcha nor the throttle this package attaches,
        //    so POST /login took unlimited un-throttled, un-captcha'd credential attempts while
        //    the /auth copy was correctly protected.
        //  - Disabling a feature removed only this package's routes; Fortify's shadow stayed
        //    reachable and answered 302 where the application expected 404.
        //
        // Registered here rather than in boot() because every provider's register() runs before
        // any provider's boot(), so this lands before Fortify::configureRoutes() reads the flag
        // whatever order the providers resolve in.
        Fortify::ignoreRoutes();

        // This package ships the routes and views, so its redirect keys are the ones an
        // application sets. The core reads its own key, so without this the preset's
        // redirects.* block was inert -- documented, configurable, and ignored. Resolved at
        // read time so a value set after boot still applies; null defers to the core.
        AuthKit::resolveRedirectsUsing(
            fn (string $key): ?string => config(key: "laranail.authkit-preset.redirects.{$key}"),
        );

        // Fortify derives several destinations from fortify.home, whose default is '/home' -- a
        // route Laravel has not shipped since Breeze replaced the old make:auth scaffolding.
        // Passkey sign-in reads it through Fortify::redirects('login'), so a successful passkey
        // login redirected to a URL that does not exist while password login went to the right
        // place. Point it at the same destination the rest of this package uses.
        config()->set('fortify.home', Support\AuthPreset::afterLoginRedirect());
        config()->set('passkeys.redirect', Support\AuthPreset::afterLoginRedirect());


        config()->set('laranail.authkit.turnstile.enabled', false);
        config()->set('laranail.captcha.provider', config('laranail.authkit-preset.bot_protection.provider', 'turnstile'));
        config()->set('laranail.captcha.credentials.source', 'config');
        config()->set('laranail.captcha.credentials.database.enabled', false);
    }

    public function packageBooted(): void
    {
        $this->registerCommands();
        $this->loadViews();
        $this->loadTranslations();
        $this->registerFortifyViews();
        $this->loadRoutes();

        $this->app->booted(function (): void {
            // Both of these have to wait for boot. laravel/passkeys merges its own config in its
            // provider, and the web middleware group is assembled from the application's
            // bootstrap, so setting either during register() is overwritten or lands nowhere.

            // laravel/passkeys derives the WebAuthn relying-party id from the host of app.url,
            // and WebAuthn forbids an IP address there. A local install writes 127.0.0.1, so
            // every ceremony fails in the browser with an opaque SecurityError. 'localhost' is a
            // valid relying-party id and is what such a host means in practice.
            $host = parse_url((string) config(key: 'app.url'), PHP_URL_HOST);

            if (is_string($host) && filter_var($host, FILTER_VALIDATE_IP) !== false) {
                config()->set('passkeys.relying_party_id', 'localhost');
            }

            // Applied to the whole web group rather than to this package's routes alone, because
            // the pages that leak are the application's own -- a dashboard, an account page --
            // not the login form. The middleware returns early for a guest.
            $this->app->make('router')->pushMiddlewareToGroup('web', PreventAuthenticatedPageCaching::class);
        });
    }

    private function registerFortifyViews(): void
    {
        if (! Features::enabled(Features::login())) {
            return;
        }

        Fortify::loginView(fn () => view(Support\AuthPreset::view('login')));

        if (Features::enabled(Features::registration())) {
            Fortify::registerView(fn () => view(Support\AuthPreset::view('register')));
        }

        if (Features::enabled(Features::passwordReset())) {
            Fortify::requestPasswordResetLinkView(fn () => view(Support\AuthPreset::view('forgot-password')));
            Fortify::resetPasswordView(fn (Request $request) => view(Support\AuthPreset::view('reset-password'), [
                'request' => $request,
            ]));
        }

        if (Features::enabled(Features::emailVerification())) {
            Fortify::verifyEmailView(fn () => view(Support\AuthPreset::view('verify-email')));
        }
    }

    private function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            InstallCommand::class,
        ]);
    }

    /**
     * The canonical namespace is the composer package name: laranail/authkit-preset.
     *
     * Blade's component-tag parser cannot use it. Its name pattern is x[-\:]([\w\-\:\.]*), which
     * has no forward slash, so <x-laranail/authkit-preset::layout /> truncates at the slash and is
     * emitted as literal text rather than compiled. The hyphen form is therefore registered as an
     * alias over the *same resolved paths* -- including the application's published override
     * directory -- so component tags keep working and both spellings find the same file.
     */
    private function loadViews(): void
    {
        $this->loadViewsFrom($this->packagePath('resources/views'), self::VIEW_NAMESPACE);

        $this->app['view']->addNamespace(
            self::COMPONENT_NAMESPACE,
            $this->app['view']->getFinder()->getHints()[self::VIEW_NAMESPACE] ?? [],
        );
    }

    private function loadTranslations(): void
    {
        // Laravel resolves overrides from lang/vendor/{namespace}, so the namespace and the
        // publish destination must agree exactly.
        $this->loadTranslationsFrom($this->packagePath('lang'), self::TRANSLATION_NAMESPACE);
    }

    private function loadRoutes(): void
    {
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        if (config(key: 'laranail.authkit-preset.routes.mode', default: 'package') !== 'package') {
            return;
        }

        $this->loadRoutesFrom($this->packagePath('routes/web.php'));
        $this->loadRoutesFrom($this->packagePath('routes/api.php'));
    }

}
