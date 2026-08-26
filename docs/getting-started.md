# Getting started

Install, run the installer, and sign in — the shortest path to a working auth UI.

## Install and scaffold

```bash
composer require laranail/authkit-preset
php artisan laranail::authkit-preset.install
```

The installer asks which features you want, which model to authenticate, and which social providers
to enable. Non-interactively:

```bash
php artisan laranail::authkit-preset.install --no-interaction \
    --stack=blade --api --password-reset --email-verification --passkeys \
    --social=google --model='App\Models\User'
```

It publishes the config, adds the traits and interfaces your selected features need to the model,
publishes only the migrations those features require, and writes the environment variables.

```bash
php artisan migrate
```

## Sign in

Visit `/auth/register`, then `/auth/login`. Routes are registered by the package, so there is
nothing to wire.

## What you get

| Route | Purpose |
|---|---|
| `GET  /auth/login` · `POST /auth/login` | Blade login |
| `GET  /auth/register` · `POST /auth/register` | Registration |
| `POST /auth/logout` | Sign out |
| `/auth/forgot-password` · `/auth/reset-password` | Password reset |
| `/auth/email/verify` | Email verification |
| `/auth/user/profile-information` · `/auth/user/password` | Account management |
| `/api/auth/*` | Sanctum token endpoints |

## Making it yours

Publish the views and edit them; the package falls back to its own copies for anything you have not
published:

```bash
php artisan vendor:publish --tag=laranail::authkit-preset-views
php artisan vendor:publish --tag=laranail::authkit-preset-lang
```

A published guest form must keep its `@csrf` and, where bot protection is on, its `<x-captcha />`.

## Where to go next

- [Configuration](configuration.md) — features, prefixes, guard, redirects
- [Route configuration](route-configuration.md) — package routes vs published routes
- [Customization](customization.md) — views, layouts, translations
- [Architecture](architecture.md) — what belongs here and what belongs in the core

---

[← Docs index](../README.md#documentation)
