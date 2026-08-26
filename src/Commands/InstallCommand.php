<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AuthKit\Preset\Commands;

use ReflectionClass;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Simtabi\Laranail\Enumerator\Rules\EnumValue;
use Simtabi\Laranail\AuthKit\Enums\SocialProvider;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\AuthKit\Preset\Enums\AuthenticationFeature;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;

class InstallCommand extends Command
{
    use SupportsNamespacedNames;

    private const string TAILWIND_BLADE_SOURCE = "@source '../../vendor/laranail/*/resources/views/**/*.blade.php';";

    private const string PASSKEYS_NPM_PACKAGE = '@laravel/passkeys';

    protected $signature = 'laranail::authkit-preset.install
        {--stack= : The frontend stack to install}
        {--social=* : Social providers to enable (google, facebook, twitter, linkedin, paypal)}
        {--api : Enable API authentication with Sanctum tokens}
        {--password-reset : Enable password reset flow}
        {--email-verification : Enable email verification flow}
        {--passkeys : Enable passkey authentication, migration, and browser client}
        {--bot-protection : Enable configurable captcha validation on guest forms}
        {--model= : The Eloquent authentication model to configure}
        {--publish-routes : Publish package route files for application ownership}
        {--publish-views : Publish Blade views for application ownership}
        {--force : Overwrite existing published files}';

    protected $description = 'Install the laranail/authkit-preset Blade resources';

    public function handle(): int
    {
        $stack = $this->option(key: 'stack') ?? prompter()->select(
            label: 'Which frontend stack would you like to install?',
            options: ['blade' => 'Blade'],
            default: 'blade',
        )->getResult();

        if ($stack !== 'blade') {
            $this->error(string: 'Only the [blade] stack is currently supported.');

            return self::FAILURE;
        }

        $authModel = $this->input->isInteractive()
            ? $this->resolveAuthModel(wantsApi: false, wantsPasskeys: false, promptWithoutFeatures: true)
            : null;

        $features = $this->resolveFeatures();
        $socialProviders = $this->resolveSocialProviders(featureSelected: in_array(needle: 'social', haystack: $features, strict: true));
        $wantsApi = in_array(needle: 'api', haystack: $features, strict: true);
        $wantsPasskeys = in_array(needle: 'passkeys', haystack: $features, strict: true);
        $wantsBotProtection = in_array(needle: 'bot-protection', haystack: $features, strict: true);

        if (count(value: $socialProviders) === 0) {
            $features = array_values(array: array_diff($features, ['social']));
        } elseif (! in_array(needle: 'social', haystack: $features, strict: true)) {
            $features[] = 'social';
        }

        if (! $this->input->isInteractive()) {
            $authModel = $this->resolveAuthModel(wantsApi: $wantsApi, wantsPasskeys: $wantsPasskeys);
        } elseif (($wantsApi || $wantsPasskeys) && $authModel === null) {
            $authModel = $this->resolveAuthModel(wantsApi: $wantsApi, wantsPasskeys: $wantsPasskeys);
        } elseif (! $wantsApi && ! $wantsPasskeys) {
            $authModel = null;
        }

        if (($wantsApi || $wantsPasskeys) && $authModel === null) {
            return self::FAILURE;
        }

        if ($authModel !== null && ! $this->configureAuthModel(model: $authModel, wantsApi: $wantsApi, wantsPasskeys: $wantsPasskeys)) {
            return self::FAILURE;
        }

        $this->publish(tag: 'laranail::authkit-config');
        $this->publish(tag: 'laranail::authkit-preset-config');
        $this->configureTailwindSource();

        if ($wantsPasskeys) {
            $this->installPasskeyFrontend();
        }

        if (count(value: $socialProviders) > 0) {
            if ($this->publishMigrations(tag: 'laranail::authkit-social-migrations', name: 'create_socials_table')) {
                $this->newLine();
                $this->info(string: 'Social login migration published. Run `php artisan migrate` to create the socials table.');
            }
        }

        if ($wantsApi) {
            if ($this->publishMigrations(tag: 'sanctum-migrations', name: 'create_personal_access_tokens_table')) {
                $this->newLine();
                $this->info(string: 'Sanctum token migration published. Run `php artisan migrate` to create the personal_access_tokens table.');
            }
        }

        if ($wantsPasskeys) {
            if ($this->publishMigrations(tag: 'laranail::authkit-passkey-migrations', name: 'create_passkeys_table')) {
                $this->newLine();
                $this->info(string: 'Passkeys migration published. Run `php artisan migrate` to create the passkeys table.');
            }

            $this->line(string: 'The @laravel/passkeys browser client and Blade event handlers were added to resources/js. Run `npm install` and rebuild your frontend assets.');
        }

        if ($authModel !== null) {
            $this->newLine();
            $this->info(string: "Authentication model configured: {$authModel}.");
        }

        if ($wantsApi || $wantsPasskeys) {
            $this->line(string: 'Migrations were published to the application database/migrations directory. If this model belongs to a module, move the migrations to that module only if its module system owns migration loading.');
        }

        if ($this->option(key: 'publish-routes')) {
            $this->publish(tag: 'laranail::authkit-preset-routes');
        }

        if ($this->option(key: 'publish-views')) {
            $this->publish(tag: 'laranail::authkit-preset-views');
        }

        $this->configureFeatures(features: $features, providers: $socialProviders);

        $this->info(string: 'laranail/authkit-preset is ready. Package routes are registered automatically.');
        $this->line(string: 'Visit /auth/register or /auth/login. Review config/laranail/authkit-preset.php to enable or disable features.');

        if ($wantsApi) {
            $this->line(string: 'API routes are enabled at /api/auth. Use Sanctum tokens for authentication.');
        }

        if (count(value: $socialProviders) > 0) {
            $this->newLine();
            $this->info(string: 'Social login enabled for: ' . implode(separator: ', ', array: $socialProviders) . '.');
            $this->line(string: 'Set your OAuth credentials in .env for each enabled provider.');
        }

        if ($wantsBotProtection) {
            $this->newLine();
            $this->info(string: 'Bot protection enabled for guest forms.');
            $this->line(string: 'Turnstile is the default provider. Set CAPTCHA_PROVIDER to use another supported provider.');
            $this->line(string: 'Add CAPTCHA_SITE_KEY and CAPTCHA_SECRET_KEY to your .env file when the selected provider requires credentials.');
        }

        $this->configureEnvironment(providers: $socialProviders, wantsBotProtection: $wantsBotProtection);

        return self::SUCCESS;
    }


    /** @return array<int, string> */
    private function resolveFeatures(): array
    {
        $explicit = [];

        foreach (['api', 'password-reset', 'email-verification', 'passkeys', 'bot-protection'] as $feature) {
            if ($this->input->hasParameterOption(values: '--' . $feature)) {
                $explicit[] = $feature;
            }
        }

        if (count(value: $this->option(key: 'social')) > 0) {
            $explicit[] = 'social';
        }

        if (! $this->input->isInteractive()) {
            return array_values(array: array_unique(array: array_merge([
                'login',
                'registration',
                'logout',
                'update-profile-information',
                'update-passwords',
            ], $explicit)));
        }

        $featureDescriptions = $this->featureDescriptions();
        $authenticationFeatures = $this->authenticationFeatures();

        $features = prompter()->multiselect(
            label: 'Which authentication feature would you like to enable?',
            options: $authenticationFeatures,
            default: array_keys(array: $authenticationFeatures),
            scroll: count(value: $authenticationFeatures),
            // into seems not to be supported by prompter
            // info: static fn (string $feature): ?string => $featureDescriptions[$feature] ?? null,
            hint: 'All features are selected by default. Press space to disable features you do not need.',
        )->getResult();

        return array_values(array: array_unique(array: array_merge($features, $explicit)));
    }

    /** @return array<int, string> */
    private function resolveSocialProviders(bool $featureSelected): array
    {
        $optionProviders = $this->option(key: 'social');

        if (count(value: $optionProviders) > 0) {
            return array_values(array: array_filter(
                array: $optionProviders,
                callback: static fn (mixed $provider): bool => is_string(value: $provider) && Validator::make(
                    data: ['provider' => $provider],
                    rules: ['provider' => [new EnumValue(enumClass: SocialProvider::class)]],
                )->passes(),
            ));
        }

        if (! $featureSelected || ! $this->input->isInteractive()) {
            return [];
        }

        return prompter()->multiselect(
            label: 'Which social login providers would you like to enable?',
            options: $this->socialProviders(),
            default: ['google'],
            required: false,
            hint: 'Google is selected by default. Enable only the providers you plan to configure.',
        )->getResult();
    }

    /** @return array<string, string> */
    private function authenticationFeatures(): array
    {
        return AuthenticationFeature::labels();
    }

    /** @return array<string, string> */
    private function featureDescriptions(): array
    {
        $descriptions = [];

        foreach (AuthenticationFeature::cases() as $feature) {
            $descriptions[$feature->value] = $feature->description() ?? '';
        }

        return $descriptions;
    }

    /** @return array<string, string> */
    private function socialProviders(): array
    {
        return SocialProvider::labels();
    }

    private function resolveAuthModel(bool $wantsApi, bool $wantsPasskeys, bool $promptWithoutFeatures = false): ?string
    {
        if (! $promptWithoutFeatures && ! $wantsApi && ! $wantsPasskeys) {
            return null;
        }

        $models = $this->eloquentAuthModels();

        if (count(value: $models) === 0) {
            if ($promptWithoutFeatures) {
                return null;
            }

            $this->error(string: 'Sanctum and passkeys require an Eloquent authentication model. No Eloquent auth provider was found in config/auth.php.');

            return null;
        }

        $requestedModel = $this->option(key: 'model');

        if ($requestedModel !== null) {
            if (! array_key_exists(key: $requestedModel, array: $models)) {
                $this->error(string: "The model [{$requestedModel}] is not configured by an Eloquent auth provider.");

                return null;
            }

            return $requestedModel;
        }

        if (! $this->input->isInteractive()) {
            if (count(value: $models) === 1) {
                return array_key_first(array: $models);
            }

            $this->error(string: 'Multiple Eloquent auth models were found. Re-run the installer with --model="App\\Models\\User".');

            return null;
        }

        $options = [];

        foreach ($models as $model => $providers) {
            $options[$model] = $model . ' (' . implode(separator: ', ', array: $providers) . ')';
        }

        return prompter()->select(
            label: 'Which auth provider should receive the authentication traits?',
            options: $options,
            default: array_key_first(array: $models),
        )->getResult();
    }

    /** @return array<string, array<int, string>> */
    private function eloquentAuthModels(): array
    {
        $models = [];

        foreach ((array) config(key: 'auth.providers', default: []) as $providerName => $provider) {
            if (! is_array(value: $provider) || ($provider['driver'] ?? null) !== 'eloquent') {
                continue;
            }

            $model = $provider['model'] ?? null;

            if (! is_string(value: $model) || $model === '' || ! is_a(object_or_class: $model, class: Model::class, allow_string: true)) {
                continue;
            }

            $models[$model] ??= [];
            $models[$model][] = (string) $providerName;
        }

        return $models;
    }

    private function configureAuthModel(string $model, bool $wantsApi, bool $wantsPasskeys): bool
    {
        if (! class_exists(class: $model)) {
            $this->error(string: "The configured authentication model [{$model}] could not be loaded.");

            return false;
        }

        $reflection = new ReflectionClass(objectOrClass: $model);
        $file = $reflection->getFileName();

        if ($file === false) {
            $this->error(string: "The authentication model [{$model}] does not have a writable source file.");

            return false;
        }

        return $this->configureModelFile(file: $file, className: $reflection->getShortName(), wantsApi: $wantsApi, wantsPasskeys: $wantsPasskeys);
    }

    private function configureModelFile(string $file, string $className, bool $wantsApi, bool $wantsPasskeys): bool
    {
        if (! is_file(filename: $file) || ! is_readable(filename: $file) || ! is_writable(filename: $file)) {
            $this->error(string: "The authentication model file [{$file}] must be readable and writable.");

            return false;
        }

        $contents = file_get_contents(filename: $file);

        if ($contents === false) {
            $this->error(string: "Unable to read the authentication model file [{$file}].");

            return false;
        }

        if ($wantsApi) {
            $contents = $this->addModelImport(contents: $contents, import: 'Laravel\\Sanctum\\HasApiTokens');
            $contents = $this->addModelTrait(contents: $contents, className: $className, trait: 'HasApiTokens');
        }

        if ($wantsPasskeys) {
            $contents = $this->addModelImport(contents: $contents, import: 'Laravel\\Fortify\\Contracts\\PasskeyUser');
            $contents = $this->addModelImport(contents: $contents, import: 'Simtabi\\Laranail\\AuthKit\\PasskeyAuthenticatable');
            $contents = $this->addModelInterface(contents: $contents, className: $className, interface: 'PasskeyUser');
            $contents = $this->addModelTrait(contents: $contents, className: $className, trait: 'PasskeyAuthenticatable');
        }

        if (file_put_contents(filename: $file, data: $contents) === false) {
            $this->error(string: "Unable to update the authentication model file [{$file}].");

            return false;
        }

        return true;
    }

    private function addModelImport(string $contents, string $import): string
    {
        $shortName = Str::afterLast(subject: $import, search: '\\');

        if (preg_match(pattern: '/^use\\s+[^;]+\\\\' . preg_quote(str: $shortName, delimiter: '/') . '(?:\\s+as\\s+' . preg_quote(str: $shortName, delimiter: '/') . ')?\\s*;/m', subject: $contents) === 1) {
            return $contents;
        }

        $updated = preg_replace(
            pattern: '/^(namespace\\s+[^;]+;)(\\R)/m',
            replacement: "$1$2use {$import};$2",
            subject: $contents,
            limit: 1,
        );

        return $updated ?? $contents;
    }

    private function addModelInterface(string $contents, string $className, string $interface): string
    {
        $updated = preg_replace_callback(
            pattern: '/(\\bclass\\s+' . preg_quote(str: $className, delimiter: '/') . '\\b)([^\\{]*)(\\{)/',
            callback: static function (array $matches) use ($interface): string {
                if (str_contains(haystack: $matches[2], needle: $interface)) {
                    // $matches[0] is the whole match; imploding the array would emit it AND its
                    // three capture groups, duplicating the class declaration on a second run.
                    return $matches[0];
                }

                if (preg_match(pattern: '/implements\\s+([^\\{]+)/', subject: $matches[2]) === 1) {
                    $header = mb_rtrim(string: $matches[2]) . ', ' . $interface;
                    $header .= mb_substr(string: $matches[2], start: mb_strlen(string: mb_rtrim(string: $matches[2])));

                    return $matches[1] . $header . $matches[3];
                }

                $header = mb_rtrim(string: $matches[2]) . ' implements ' . $interface;
                $header .= mb_substr(string: $matches[2], start: mb_strlen(string: mb_rtrim(string: $matches[2])));

                return $matches[1] . $header . $matches[3];
            },
            subject: $contents,
            limit: 1,
        );

        return $updated ?? $contents;
    }

    private function addModelTrait(string $contents, string $className, string $trait): string
    {
        if (preg_match(pattern: '/^\\s*use\\s+' . preg_quote(str: $trait, delimiter: '/') . '\\s*;/m', subject: $contents) === 1) {
            return $contents;
        }

        $updated = preg_replace(
            pattern: '/(\\bclass\\s+' . preg_quote(str: $className, delimiter: '/') . '\\b[^\\{]*\\{)(\\R)/',
            replacement: "$1$2    use {$trait};$2",
            subject: $contents,
            limit: 1,
        );

        return $updated ?? $contents;
    }

    private function configureTailwindSource(?string $cssPath = null): bool
    {
        $cssPath ??= base_path(path: 'resources/css/app.css');

        if (! file_exists(filename: $cssPath)) {
            return false;
        }

        $contents = file_get_contents(filename: $cssPath);

        if (str_contains(haystack: $contents, needle: self::TAILWIND_BLADE_SOURCE)) {
            return false;
        }

        $frameworkSource = "@source '../../storage/framework/views/*.php';";

        if (str_contains(haystack: $contents, needle: $frameworkSource)) {
            $replacementCount = 0;
            $contents = str_replace(
                search: $frameworkSource,
                replace: $frameworkSource . "\n" . self::TAILWIND_BLADE_SOURCE,
                subject: $contents,
                count: $replacementCount,
            );
        } else {
            $contents = mb_rtrim(string: $contents, characters: "\n") . "\n\n" . self::TAILWIND_BLADE_SOURCE . "\n";
        }

        file_put_contents(filename: $cssPath, data: $contents);

        return true;
    }

    private function installPasskeyFrontend(?string $packagePath = null, ?string $appJsPath = null, ?string $passkeysJsPath = null): bool
    {
        $packagePath ??= base_path(path: 'package.json');
        $appJsPath ??= base_path(path: 'resources/js/app.js');
        $passkeysJsPath ??= base_path(path: 'resources/js/passkeys.js');
        $changed = false;

        if (file_exists(filename: $packagePath)) {
            $package = json_decode(json: (string) file_get_contents(filename: $packagePath), associative: true);

            if (! is_array(value: $package)) {
                $this->warn(string: 'Could not update package.json because it does not contain valid JSON.');
            } elseif (! isset($package['dependencies'][self::PASSKEYS_NPM_PACKAGE])
                && ! isset($package['devDependencies'][self::PASSKEYS_NPM_PACKAGE])) {
                $package['dependencies'] ??= [];
                $package['dependencies'][self::PASSKEYS_NPM_PACKAGE] = '^0.2.0';
                ksort(array: $package['dependencies']);
                file_put_contents(filename: $packagePath, data: json_encode(value: $package, flags: JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
                $changed = true;
            }
        }

        $sourcePath = __DIR__ . '/../../resources/js/passkeys.js';

        if (! file_exists(filename: $passkeysJsPath) && file_exists(filename: $sourcePath)) {
            $directory = dirname(path: $passkeysJsPath);

            if (! is_dir(filename: $directory)) {
                mkdir(directory: $directory, permissions: 0755, recursive: true);
            }

            copy(from: $sourcePath, to: $passkeysJsPath);
            $changed = true;
        }

        $import = "import './passkeys';";

        if (! file_exists(filename: $appJsPath)) {
            $directory = dirname(path: $appJsPath);

            if (! is_dir(filename: $directory)) {
                mkdir(directory: $directory, permissions: 0755, recursive: true);
            }

            file_put_contents(filename: $appJsPath, data: $import . "\n");
            $changed = true;
        } else {
            $contents = file_get_contents(filename: $appJsPath);

            if ($contents !== false && ! preg_match(pattern: '/import\s+[\'\"]\.\/passkeys[\'\"]\s*;/', subject: $contents)) {
                file_put_contents(filename: $appJsPath, data: mb_rtrim(string: $contents, characters: "\n") . "\n\n" . $import . "\n");
                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * @param array<int, string> $features
     * @param array<int, string> $providers
     */
    private function configureFeatures(array $features, array $providers, ?string $configPath = null): void
    {
        $configPath ??= config_path(path: 'laranail/authkit-preset.php');

        if (! file_exists(filename: $configPath)) {
            return;
        }

        $contents = file_get_contents(filename: $configPath);
        $featureMethods = [
            'login'                      => 'login',
            'registration'               => 'registration',
            'logout'                     => 'logout',
            'update-profile-information' => 'updateProfileInformation',
            'update-passwords'           => 'updatePasswords',
            'social'                     => 'social',
            'api'                        => 'api',
            'password-reset'             => 'passwordReset',
            'email-verification'         => 'emailVerification',
            'passkeys'                   => 'passkeys',
            'bot-protection'             => 'botProtection',
        ];
        $featureLines = [];

        foreach ($featureMethods as $feature => $method) {
            if (in_array(needle: $feature, haystack: $features, strict: true) && ($feature !== 'social' || count(value: $providers) > 0)) {
                $featureLines[] = "        \\Simtabi\\Laranail\\AuthKit\\Preset\\Features::{$method}(),";
            }
        }

        $featureBlock = "    'features' => [\n" . implode(separator: "\n", array: $featureLines) . "\n    ],";
        $contents = preg_replace(
            pattern: "/    'features'\s*=>\s*\[(?:.|\R)*?\n    \],/",
            replacement: $featureBlock,
            subject: $contents,
            limit: 1,
        ) ?? $contents;

        $providerArray = "['" . implode(separator: "', '", array: $providers) . "']";
        $contents = preg_replace(
            pattern: "/'providers'\s*=>\s*\[[^\]]*\]/",
            replacement: "'providers' => {$providerArray}",
            subject: $contents,
            limit: 1,
        ) ?? $contents;

        file_put_contents(filename: $configPath, data: $contents);
    }

    /** @param array<int, string> $providers */
    private function configureEnvironment(array $providers, bool $wantsBotProtection, ?string $envPath = null, ?string $envExamplePath = null): void
    {
        $envPath ??= base_path(path: '.env');
        $envExamplePath ??= base_path(path: '.env.example');
        $variables = [];

        foreach ($providers as $provider) {
            $upper = Str::upper(value: $provider);
            $variables["AUTHKIT_{$upper}_CLIENT_ID"] = '';
            $variables["AUTHKIT_{$upper}_CLIENT_SECRET"] = '';
            $variables["AUTHKIT_{$upper}_REDIRECT"] = url(path: "/auth/social/{$provider}/callback");
        }

        if ($wantsBotProtection) {
            $variables['CAPTCHA_PROVIDER'] = 'turnstile';
            $variables['CAPTCHA_SITE_KEY'] = '';
            $variables['CAPTCHA_SECRET_KEY'] = '';
        }

        if (count(value: $variables) === 0) {
            return;
        }

        foreach ([$envPath, $envExamplePath] as $path) {
            $this->appendMissingEnvironmentVariables(path: $path, variables: $variables);
        }
    }

    /** @param array<string, string> $variables */
    private function appendMissingEnvironmentVariables(string $path, array $variables): void
    {
        if (! file_exists(filename: $path)) {
            return;
        }

        $existing = file_get_contents(filename: $path);

        if ($existing === false) {
            return;
        }

        $missing = [];

        foreach ($variables as $key => $value) {
            if (preg_match(pattern: '/^\s*(?:export\s+)?' . preg_quote(str: $key, delimiter: '/') . '\s*=/m', subject: $existing) === 1) {
                continue;
            }

            $missing[$key] = $value;
        }

        if (count(value: $missing) === 0) {
            return;
        }

        $lines = [];

        foreach ($missing as $key => $value) {
            $lines[] = "{$key}={$value}";
        }

        file_put_contents(filename: $path, data: mb_rtrim(string: $existing, characters: "\n") . "\n\n# Auth Kit environment variables\n" . implode(separator: "\n", array: $lines) . "\n");
    }

    private function publish(string $tag): void
    {
        $parameters = ['--tag' => $tag];

        if ($this->option(key: 'force')) {
            $parameters['--force'] = true;
        }

        $this->call(command: 'vendor:publish', arguments: $parameters);
    }

    /**
     * Publish a migration group only when the application does not already have it.
     *
     * `vendor:publish` stamps a fresh timestamp onto a migration's destination on every run, so
     * publishing the same group twice leaves two files declaring the same table and `migrate`
     * dies with "table already exists". Laravel has no built-in guard for a re-run, so the
     * installer checks for the migration by name before publishing it.
     */
    private function publishMigrations(string $tag, string $name, ?string $migrationPath = null): bool
    {
        if ($this->migrationExists(name: $name, migrationPath: $migrationPath)) {
            $this->newLine();
            $this->line(string: "Skipped [{$name}]: the application already has this migration.");

            return false;
        }

        $this->publish(tag: $tag);

        return true;
    }

    private function migrationExists(string $name, ?string $migrationPath = null): bool
    {
        $migrationPath ??= database_path(path: 'migrations');

        if (! is_dir(filename: $migrationPath)) {
            return false;
        }

        // Published migrations are prefixed with a timestamp, so match on the trailing name.
        return glob(pattern: $migrationPath . '/*_' . $name . '.php') !== [];
    }
}
