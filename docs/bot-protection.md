# Bot protection

Bot protection is optional. Enable it with `--bot-protection` or `Features::botProtection()`, then configure a provider supported by `laranail/captcha`:

```env
CAPTCHA_PROVIDER=turnstile
CAPTCHA_SITE_KEY=
CAPTCHA_SECRET_KEY=
```

The provider is selected through `laranail.authkit-preset.bot_protection.provider`; the service provider configures `laranail/captcha` to use it and validates the required `captcha` request input on the server. The preset attaches `ValidateCaptcha` to the named package web submissions below after routes load, so it applies even though only the login route declares it inline:

| Protected route              | Feature required |
|------------------------------|------------------|
| `POST /auth/login`           | Login            |
| `POST /auth/register`        | Registration     |
| `POST /auth/forgot-password` | Password reset   |
| `POST /auth/reset-password`  | Password reset   |

The supplied login form renders `<x-captcha />` when the feature is enabled. Confirm that any published replacement for every protected form sends the provider response in the `captcha` field; validation failure stops the request before authentication or reset handling. API routes are not given this middleware, so API clients need an application-owned anti-abuse policy if that surface is public.

Turnstile is the default provider. Use production site and secret keys only on the production origin, clear cached configuration after changing them, and deliberately test a valid and invalid challenge. CAPTCHA complements rather than replaces the explicit route limits: guest authentication and reset submissions are limited to ten requests per minute, verification operations to six, and the API group to sixty.

Cloudflare's published always-pass test keys return the same fixed dummy token on every render. In a local app using those keys, disable the application's replay guard with `CAPTCHA_REPLAY_GUARD_ENABLED=false`; otherwise a later form submission is correctly rejected as a replay of that fixed token. Keep the replay guard enabled with real provider credentials and in production.

For production safeguards, see [security](security.md). For package and application verification, see [testing](testing.md).

---

[← Docs index](../README.md#documentation)
