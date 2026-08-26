# Configuration

Everything the preset reads lives under `laranail.authkit-preset`.

## Where the config lives

| | |
|---|---|
| Package default | `config/laranail/authkit-preset.php` inside the package |
| Published to | `config/laranail/authkit-preset.php` in the application |
| Config key | `laranail.authkit-preset` |
| Publish tag | `laranail::authkit-preset-config` |

The key is namespaced because Laravel's config is one flat map underneath. A package claiming a bare
`authkit-preset` key would sit one collision away from any other package, and the failure would be
silent.

## Keys

| Key | Default | What it does |
|---|---|---|
| `stack` | `blade` | The frontend stack to render. Blade is the only one implemented. |
| `features` | see below | Which flows are registered. A feature that is off registers no routes and renders no UI for itself. |
| `guard` | `web` | The guard the preset's routes authenticate against. |
| `prefix.web` | `auth` | Web route prefix — `/auth/login`, `/auth/register`. |
| `prefix.api` | `api/auth` | API route prefix. |
| `middleware.web` · `middleware.api` | `['web']` · `['api','throttle:60,1']` | Middleware applied to each group. |
| `routes.mode` | `package` | `package` registers routes from the package; `published` expects the application to require the published files. |
| `redirects.*` | `/dashboard`, `/` | Where each flow lands. |
| `bot_protection.provider` | `turnstile` | Which captcha provider `laranail/captcha` uses. |
| `social.providers` | `['google']` | Which providers render on the login page. |

## Features

```php
'features' => [
    \Simtabi\Laranail\AuthKit\Preset\Features::login(),
    \Simtabi\Laranail\AuthKit\Preset\Features::registration(),
    \Simtabi\Laranail\AuthKit\Preset\Features::logout(),
    // updateProfileInformation, updatePasswords, emailVerification,
    // passwordReset, social, api, passkeys, botProtection
],
```

Removing an entry removes that flow's routes and the UI that links to it.

## Environment

| Variable | Maps to |
|---|---|
| `AUTHKIT_PRESET_STACK` | `stack` |
| `AUTHKIT_PRESET_GUARD` | `guard` |
| `AUTHKIT_PRESET_WEB_PREFIX` · `AUTHKIT_PRESET_API_PREFIX` | `prefix.*` |
| `AUTHKIT_PRESET_ROUTES_MODE` | `routes.mode` |
| `AUTHKIT_PRESET_AFTER_LOGIN` and siblings | `redirects.*` |
| `CAPTCHA_PROVIDER` · `CAPTCHA_SITE_KEY` · `CAPTCHA_SECRET_KEY` | bot protection |

Core settings — the guard the actions use, rate limits, social credentials — live under
`laranail.authkit`. See the [core's configuration guide](../../authkit/docs/configuration.md).

## After changing anything

```bash
php artisan config:clear
php artisan route:list
```

Prefixes, guard and route mode all change the actual route surface. Read it back rather than
assuming.

---

[← Docs index](../README.md#documentation)
