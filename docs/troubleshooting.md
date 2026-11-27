# Troubleshooting

## Installation cannot resolve dependencies

Use the full repository block in [Installation](installation.md). Composer reads repositories only
from the host application. Run the installer with `--dry-run --no-interaction` and explicit options
to inspect intended changes without writing files. See [Installer](installer.md).

## Forms have no styles or passkey buttons do nothing

Build the host assets. Tailwind must scan the package views; custom CSS entry points need the
correct relative `@source` path. Passkeys additionally need `@laravel/passkeys` installed and the
browser code included in the host entry point. See [Passkeys](passkeys.md).

## Routes or redirects do not match

Run `php artisan config:clear` and `php artisan route:list`. Check `laranail.authkit-preset.guard`,
`prefix.web`, `route_name_prefix`, features and `routes.mode`. With published mode the application
must load the published web route file. Core API settings are separate. See [Route configuration](route-configuration.md).

## Published views ignore updates

The application's copy overrides the vendor template. Compare it with the upgraded package and
merge form, route, CSRF and validation changes deliberately. Views publish to
`resources/views/vendor/laranail/authkit-preset`. Clear compiled views after changes:

```bash
php artisan view:clear
```

## CAPTCHA, social login or recovery codes fail

Configure captcha credentials for the selected provider and retain the captcha field in published
forms. Social login requires the optional package and its credentials; check
[Social login](social-login.md). Recovery codes need a text input accepting the full 21-character
alphanumeric code. Do not use numeric-only input constraints.

## API requests unexpectedly return JSON or fail to find a page

The core owns `/api/auth`; the preset owns browser pages under `/auth`. Follow
[API routes](api-routes.md) for bearer-token setup, and [Feature scope](feature-scope.md) before
assuming a browser feature also exposes a token API.

---

[← Docs index](../README.md#documentation)
