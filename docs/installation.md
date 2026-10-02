# Installation

Laranail packages are temporarily installed from their Git repositories rather than Packagist. Add the VCS repositories listed in the [README](../README.md#installation) to the consuming application's `composer.json`, then install and run the Blade installer:

```bash
composer require laranail/authkit-preset
php artisan laranail::authkit-preset.install
```

Blade is the only supported stack. The interactive installer asks for the authentication provider/model and presents all preset features as selected by default. It publishes `config/laranail/authkit.php` and `config/laranail/authkit-preset.php`, configures Tailwind to scan the package views, and leaves preset routes enabled by default. Social login is an optional package; install `laranail/authkit-social-login` and use its installer to configure providers. When installed, the preset renders its button component on login and registration pages.

## Automated installation

Use explicit options in CI or provisioning scripts:

```bash
php artisan laranail::authkit-preset.install \
    --password-reset \
    --email-verification \
    --api \
    --passkeys \
    --model='App\Models\User' \
    --bot-protection \
    --publish-views
```

The normal login, registration, logout, profile, and password-update features remain enabled for a non-interactive installation. Add each optional preset feature flag deliberately. `--publish-routes` and `--publish-views` give the application ownership of those resources instead of requiring a package fork. If routes are published, set `AUTHKIT_PRESET_ROUTES_MODE=published` and load them from the application route bootstrap as described in [route configuration](route-configuration.md).

## Complete the selected setup

| Selection      | Installer action                                                                                                    | Required follow-up                                                                                                                          |
|----------------|---------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------|
| API            | Adds Sanctum's `HasApiTokens` trait to the selected model and publishes its migration.                              | Run `php artisan migrate`; see [API routes](api-routes.md).                                                                                  |
| Passkeys       | Adds the Auth Kit passkey trait/interface, publishes its migration, and adds `@laravel/passkeys` plus browser code. | Run `npm install`, build assets, migrate, and see [Passkeys](passkeys.md).                                                                  |
| Bot protection | Enables the feature in preset configuration.                                                                        | Set the selected captcha provider's environment variables; see [Bot protection](bot-protection.md).                                           |

## Verify the installation

After migration and asset work, inspect `config/laranail/authkit-preset.php` for the selected guard, features, prefixes, middleware, redirects, and CAPTCHA provider. Run `php artisan route:list` and confirm that only intended `/auth` and `/api/auth` routes exist. Visit `/auth/register` or `/auth/login`; for API installations, obtain a token through the documented register/login endpoint and exercise a protected request. Review the generated configuration, mail delivery, and CAPTCHA behavior before exposing routes outside a local environment.

---

[← Docs index](../README.md#documentation)
