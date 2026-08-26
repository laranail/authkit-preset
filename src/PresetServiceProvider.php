<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AuthKit\Preset;

use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;
use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\AuthKit\Preset\Commands\InstallCommand;
use Simtabi\Laranail\AuthKit\Preset\Http\Middleware\ValidateCaptcha;
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
        $this->mergeConfigFrom(__DIR__ . '/../config/laranail/authkit-preset.php', 'laranail.authkit-preset');

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
            $this->registerCaptchaMiddleware();
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
        $this->loadViewsFrom(__DIR__ . '/../resources/views', self::VIEW_NAMESPACE);

        $this->app['view']->addNamespace(
            self::COMPONENT_NAMESPACE,
            $this->app['view']->getFinder()->getHints()[self::VIEW_NAMESPACE] ?? [],
        );
    }

    private function loadTranslations(): void
    {
        // Laravel resolves overrides from lang/vendor/{namespace}, so the namespace and the
        // publish destination must agree exactly.
        $this->loadTranslationsFrom(__DIR__ . '/../lang', self::TRANSLATION_NAMESPACE);
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

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
    }

    private function registerCaptchaMiddleware(): void
    {
        foreach (['login.store', 'register.store', 'password.email', 'password.update'] as $name) {
            $route = app('router')->getRoutes()->getByName($name);

            if ($route !== null && in_array('web', $route->middleware(), true)) {
                $route->middleware(ValidateCaptcha::class);
            }
        }
    }
}
