# laranail/authkit-preset

[![Packagist Version](https://img.shields.io/packagist/v/laranail/authkit-preset.svg?style=flat-square)](https://packagist.org/packages/laranail/authkit-preset)
[![Tests](https://img.shields.io/github/actions/workflow/status/laranail/authkit-preset/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/laranail/authkit-preset/actions/workflows/tests.yml)
[![Static analysis](https://img.shields.io/github/actions/workflow/status/laranail/authkit-preset/static.yml?branch=main&label=static%20analysis&style=flat-square)](https://github.com/laranail/authkit-preset/actions/workflows/static.yml)
[![License MIT](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

Blade authentication scaffolding for Laravel 13+, powered by [`laranail/authkit`](https://github.com/laranail/authkit).

> [!WARNING]
> This package is still in development. Breaking changes are imminent; use it in production at your own risk.

The preset provides configurable web and API authentication routes, Fortify-backed password and profile flows, Blade views, social login integration, passkeys, and optional captcha-based bot protection.

## Requirements

- PHP 8.4+
- Laravel 13.x

## Install

`laranail/*` packages resolve through git, not Packagist. Add the repositories block to your
application's `composer.json` — Composer ignores a dependency's own `repositories`, so it must list
the whole transitive closure:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/laranail/authkit.git" },
    { "type": "vcs", "url": "https://github.com/laranail/console.git" },
    { "type": "vcs", "url": "https://github.com/laranail/enumerator.git" },
    { "type": "vcs", "url": "https://github.com/laranail/package-tools.git" },
    { "type": "vcs", "url": "https://github.com/laranail/captcha.git" },
    { "type": "vcs", "url": "https://github.com/laranail/db-tools.git" }
]
```

Then:

```bash
composer require laranail/authkit-preset
php artisan laranail::authkit-preset.install
```

See [installation](docs/installation.md) for the full walkthrough.

## <a name="documentation"></a>Documentation

Full documentation: <https://opensource.simtabi.com/documentation/laranail/authkit-preset/>

### Guides

- [Installation](docs/installation.md) — the repositories block, the installer, what it publishes
- [Getting started](docs/getting-started.md) — the shortest path to a working auth UI
- [Configuration](docs/configuration.md) — features, prefixes, guard, redirects, bot protection
- [Route configuration](docs/route-configuration.md) — package routes vs published routes
- [Architecture](docs/architecture.md) — what belongs here and what belongs in the core
- [Security](docs/security.md) — CSRF, throttling, captcha coverage
- [Release](docs/release.md) — versioning, and why the core is tagged first

### Reference

- [Login](docs/login.md) · [Registration](docs/registration.md) · [Logout](docs/logout.md)
- [Password reset](docs/password-reset.md) · [Password updates](docs/password-updates.md)
- [Profile management](docs/profile-management.md) · [Email verification](docs/email-verification.md)
- [Social login](docs/social-login.md) · [Passkeys](docs/passkeys.md) · [API routes](docs/api-routes.md)
- [Bot protection](docs/bot-protection.md)

### Recipes

- [Customization](docs/customization.md) — publishing views, layouts and translations

### Project

- [Testing](docs/testing.md) — how the suite is arranged and what it does not cover
- [Changelog](CHANGELOG.md) · [Contributing](CONTRIBUTING.md) · [Security policy](SECURITY.md)

## Installer options

The installer can be run non-interactively with explicit options:

```bash
php artisan laranail::authkit-preset.install \
    --password-reset \
    --email-verification \
    --api \
    --passkeys \
    --model='App\Models\User' \
    --bot-protection \
    --social=google \
    --social=linkedin
```

Available options:

| Option                 | Description                                                              |
|------------------------|--------------------------------------------------------------------------|
| `--stack=blade`        | Select the frontend stack. Blade is currently supported.                 |
| `--social=<provider>`  | Enable a supported social provider. Repeat for multiple providers.       |
| `--api`                | Enable API authentication and publish the Sanctum token migration.       |
| `--password-reset`     | Enable forgot-password and reset-password flows.                         |
| `--email-verification` | Enable email verification.                                               |
| `--passkeys`           | Enable passkey authentication, migration, and browser client.            |
| `--model=<class>`      | Select the Eloquent auth model to configure for Sanctum and/or passkeys. |
| `--bot-protection`     | Enable captcha validation on registration and password-reset forms.      |
| `--publish-routes`     | Publish route files for application ownership.                           |
| `--publish-views`      | Publish Blade views for application customization.                       |
| `--force`              | Overwrite existing published files.                                      |

Supported social providers are `google`, `facebook`, `twitter`, `linkedin`, and `paypal`.

In interactive mode, the installer asks which auth provider should receive authentication traits immediately after the frontend stack, then asks `Which authentication feature would you like to enable?` and shows a description for every choice. This includes API authentication, which is selected by default and publishes the Sanctum migration. Social login opens a second multi-select for its providers with Google selected by default; enable only providers you plan to configure. The installer reads the `eloquent` providers from `config/auth.php` and applies traits to the selected provider's model when API authentication or passkeys are enabled. In non-interactive mode, only the base web features are enabled unless optional feature flags are supplied; use `--model=<class>` when needed.

The selected model receives `Laravel\Sanctum\HasApiTokens` when API authentication is enabled. When passkeys are enabled, it receives the `Laravel\Fortify\Contracts\PasskeyUser` interface and authkit's `Simtabi\Laranail\AuthKit\PasskeyAuthenticatable` trait. The model source file must be writable.

When passkeys are enabled, the installer adds `@laravel/passkeys` to `package.json`, copies the passkey browser adapter to `resources/js/passkeys.js`, and imports it from `resources/js/app.js`. Run `npm install` and rebuild your Vite assets after installation. The adapter binds the preset's login, registration, and deletion buttons to Fortify's canonical passkey endpoints; it does not reimplement WebAuthn.

## Configuration

The installer publishes both configuration files:

- `config/laranail/authkit.php` contains backend authentication, Fortify, and social settings.
- `config/laranail/authkit-preset.php` controls the frontend stack, bot-protection provider, enabled features, route prefixes, middleware, guard, and redirects.

Enable or disable preset features in `config/laranail/authkit-preset.php`:

```php
'features' => [
    Features::login(),
    Features::registration(),
    Features::logout(),
    Features::passwordReset(),
    Features::emailVerification(),
],
```

Bot protection is disabled by default. When enabled with `Features::botProtection()`, the preset uses `laranail/captcha` and defaults to Turnstile. Credentials always resolve from configuration, never the database:

```env
CAPTCHA_PROVIDER=turnstile
CAPTCHA_SITE_KEY=
CAPTCHA_SECRET_KEY=
```

Set `CAPTCHA_PROVIDER` to any provider supported by `laranail/captcha`; the Blade markup and validation remain unchanged. Bot protection applies only to the web registration, forgot-password, and reset-password submissions. Login and API requests are not challenged.

### Passkey frontend

Passkey support requires both Fortify's server-side routes and the official browser client. Enabling passkeys with the installer performs the frontend wiring automatically:

```bash
php artisan laranail::authkit-preset.install --passkeys --model='App\\Models\\User'
npm install
npm run build
```

The generated `resources/js/passkeys.js` uses `@laravel/passkeys` for login, registration, and credential deletion. Keep `resources/js/app.js` in the Vite input list; the preset's Blade layout loads that bundle when the application has a Vite manifest or development server.

Route prefixes and redirects can also be customized through `config/laranail/authkit-preset.php` or its environment variables:

```env
AUTHKIT_PRESET_WEB_PREFIX=auth
AUTHKIT_PRESET_API_PREFIX=api/auth
AUTHKIT_PRESET_GUARD=web
AUTHKIT_PRESET_AFTER_LOGIN=/dashboard
AUTHKIT_PRESET_AFTER_REGISTRATION=/dashboard
```

## Publish resources independently

The preset and Auth Kit expose separate Laravel publish tags. Publish only the resources your application needs instead of running the installer.

### Configuration

```bash
php artisan vendor:publish --tag=laranail::authkit-config
php artisan vendor:publish --tag=laranail::authkit-preset-config
```

Use `--force` to overwrite an existing published file:

```bash
php artisan vendor:publish --tag=laranail::authkit-preset-config --force
```

### Migrations

The preset does not add a migration of its own. Auth Kit provides optional migrations for social accounts and passkeys:

```bash
php artisan vendor:publish --tag=laranail::authkit-social-migrations
php artisan vendor:publish --tag=laranail::authkit-passkey-migrations
php artisan migrate
```

When API authentication is enabled, publish Sanctum's migration as well:

```bash
php artisan vendor:publish --tag=sanctum-migrations
php artisan migrate
```

Only publish the migration groups for features enabled in `config/laranail/authkit-preset.php`. These migrations are published to the application's `database/migrations` directory because their tables belong to the application's database. If the selected model lives in a module, the model location does not alter the schema; move the published files into the module's migration directory only when that module owns and loads its migrations.

### Routes

Publish the web and API route files to `routes/laranail-authkit-preset-web.php` and `routes/laranail-authkit-preset-api.php`:

```bash
php artisan vendor:publish --tag=laranail::authkit-preset-routes
```

Set the route mode to `published` so the package stops loading its bundled route files, then register the published files from the application's route bootstrap:

```php
// config/laranail/authkit-preset.php
'routes' => [
    'mode' => 'published',
],
```

Require the files from the application's route-loading entry point:

```php
require base_path('routes/laranail-authkit-preset-web.php');
require base_path('routes/laranail-authkit-preset-api.php');
```

### Blade views

Publish the views to `resources/views/vendor/laranail-authkit-preset`:

```bash
php artisan vendor:publish --tag=laranail::authkit-preset-views
```

The published page views can be edited without modifying the package. They include the login, registration, password, profile, email-verification, and passkey views. The preset's reusable components continue to be loaded from the package namespace.

## Routes and views without publishing

For the standard setup, leave `laranail.authkit-preset.routes.mode` as `package`. The service provider loads the package routes automatically and Fortify uses the preset's Blade views. Publish resources only when the application needs to own and customize them.

## Testing

From the package directory:

```bash
composer test
composer lint
```

## Sister packages

| Package | Role |
|---|---|
| [`laranail/authkit`](https://github.com/laranail/authkit) | Headless core — actions, contracts, result objects, REST API |
| [`laranail/authkit-preset`](https://github.com/laranail/authkit-preset) | Blade scaffolding on top of the core |
| `laranail/authkit-sso` | SAML 2.0 and OIDC single sign-on |
| `laranail/authkit-oauth` | OAuth and social identity |
| `laranail/authkit-tenancy` | Multi-tenancy |
| `laranail/authkit-ldap` | LDAP and Active Directory |

The family shares one root namespace, `Simtabi\Laranail\AuthKit\`, with each sibling a segment
under it.

## Contributing and security

See [CONTRIBUTING.md](CONTRIBUTING.md). Report security issues privately — [SECURITY.md](SECURITY.md).

## License

MIT licensed.
