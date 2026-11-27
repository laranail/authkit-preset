# laranail/authkit-preset

[![Tests](https://github.com/laranail/authkit-preset/actions/workflows/tests.yml/badge.svg)](https://github.com/laranail/authkit-preset/actions/workflows/tests.yml)
[![Code style](https://github.com/laranail/authkit-preset/actions/workflows/code-style.yml/badge.svg)](https://github.com/laranail/authkit-preset/actions/workflows/code-style.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> Blade authentication scaffolding for AuthKit: published forms, browser routes and optional passkey and captcha setup.

Requires PHP `^8.4.1 || ^8.5` and Laravel `^13.0`. This package is pre-1.0; review
[feature scope](docs/feature-scope.md) before choosing the flows to enable.

## Install

`laranail/*` packages resolve through git, not Packagist. Add the repositories block to your
application's `composer.json` — Composer ignores a dependency's own `repositories`, so it must list
the whole transitive closure:

```json
"repositories": [
    {"type": "vcs", "url": "https://github.com/laranail/authkit-preset.git"},
    {"type": "vcs", "url": "https://github.com/laranail/authkit.git"},
    {"type": "vcs", "url": "https://github.com/laranail/console.git"},
    {"type": "vcs", "url": "https://github.com/laranail/enumerator.git"},
    {"type": "vcs", "url": "https://github.com/laranail/package-tools.git"},
    {"type": "vcs", "url": "https://github.com/laranail/captcha.git"},
    {"type": "vcs", "url": "https://github.com/laranail/db-tools.git"},
    {"type": "composer", "url": "https://repo.packagist.org", "exclude": ["laranail/*"]},
    {"packagist.org": false}
]
```

Then:

```bash
composer require laranail/authkit-preset
php artisan laranail::authkit-preset.install
```

See [installation](docs/installation.md) for the full walkthrough.

## Quick start guide and usage

### Getting started

1. Run the migrations the installer published for your selected features:

   ```bash
   php artisan migrate
   ```

2. Rebuild the assets so Tailwind picks up the package views (run `npm install` first if you enabled
   passkeys):

   ```bash
   npm run build
   ```

3. Visit `/auth/register`, then `/auth/login`. Routes are registered by the package, so there is
   nothing to wire.

### Usage

```blade
{{-- resources/views/layouts/app.blade.php: the installer has already registered every route --}}
@auth
    <form method="POST" action="{{ route('laranail-auth.logout') }}">
        @csrf
        <button type="submit">Sign out</button>
    </form>
@else
    <a href="{{ route('laranail-auth.login') }}">Sign in</a>
    <a href="{{ route('laranail-auth.register') }}">Create an account</a>
@endauth
```

The full walkthrough is in [Getting started](docs/getting-started.md); everything else is in the [documentation index](#documentation).

## <a name="documentation"></a>Documentation

The documentation lives in this repository under [`docs/`](docs/).

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
- [Social login](docs/social-login.md) · [Passkeys](docs/passkeys.md) · [Two-factor authentication](https://github.com/laranail/authkit/blob/main/docs/two-factor-authentication.md) · [API routes](docs/api-routes.md)
- [Bot protection](docs/bot-protection.md)

### Recipes

- [Customization](docs/customization.md) — publishing views, layouts and translations

### Project

- [Testing](docs/testing.md) — how the suite is arranged and what it does not cover
- [Changelog](CHANGELOG.md) · [Contributing](CONTRIBUTING.md) · [Security policy](SECURITY.md)

### Operations

- [Environment variables](docs/environment.md) — supported names and cached configuration
- [Localization](docs/localization.md) — language files, overrides and fallback
- [Troubleshooting](docs/troubleshooting.md) — setup checks and common failures
- [Feature scope](docs/feature-scope.md) — supported flows, limitations and deferred work
- [Installer](docs/installer.md) — previews and repeatable skipped steps

## Contributing & security

See [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities privately through the
[security policy](SECURITY.md). Participation follows the
[Laranail Code of Conduct](https://github.com/laranail/.github/blob/main/CODE_OF_CONDUCT.md).

## License

MIT © Simtabi LLC. See [LICENSE](LICENSE).
