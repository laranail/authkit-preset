# Route configuration

The service provider loads `routes/web.php` only while `laranail.authkit-preset.routes.mode` is `package` (the default). Web URLs use `AUTHKIT_PRESET_WEB_PREFIX=auth`; API URLs ship from `laranail/authkit` and use `AUTHKIT_API_PREFIX=api/auth`. Prefixes do not include a leading slash in configuration, though the resulting URLs do. After changing either one, update frontend links, OAuth provider callback URLs, API client configuration, and tests.

## Route mode

Set `AUTHKIT_PRESET_ROUTES_MODE=published` only after publishing the routes and loading `routes/laranail-authkit-preset-web.php` and `routes/laranail-authkit-preset-api.php` from the application's route bootstrap. In published mode the package intentionally loads neither route file; forgetting the application `require` produces no preset routes. This mode is the appropriate place to change controllers, middleware, names, or authorization policy.

## Guards and middleware

Guest web pages use the configured `web` middleware list plus `guest:<AUTHKIT_PRESET_GUARD>`. Logout, profile, password update, passkey management, verification prompt, and verification actions use the same web middleware plus `auth:<guard>`. API routes ship from `laranail/authkit` and use `laranail.authkit.api.middleware` (`api`, `throttle:60,1` by default); protected API account routes add `auth:sanctum`.

The package also registers `GET /dashboard`, independent of the web prefix, with the web middleware and `auth:<guard>`. It is a basic authenticated view and not a substitute for application authorization; replace or remove it when it does not suit the application.

## More than one user population

The web routes are mounted once per configured population, so an application with staff on an
`admin` guard alongside customers on `web` gets both sets:

```php
// config/laranail/authkit-preset.php
'guards' => [
    'admin' => ['prefix' => 'admin/auth', 'name' => 'admin.'],
],
```

Both keys are optional and default to `<guard>/<web prefix>` and `<guard>.`. The guard must exist in
`config/auth.php`. Every mount requires the same route file rather than duplicating it, so a route
added for one population cannot be forgotten for the others.

`GET /dashboard` is the exception: it sits outside the prefix at a bare path, so it belongs to the
primary mount only. A second population registering it again would claim the same URI behind its own
guard and take the page away from the first; additional populations get a prefixed dashboard instead.

## Route names

Route names sit in a flat global registry shared with the application and every other package, so
they carry a vendor prefix — `laranail-auth.login`, not `login`. `AUTHKIT_PRESET_ROUTE_NAME_PREFIX`
changes it, and `''` restores bare names.

Scoping them does not break the code that resolves the bare ones. Laravel's own guest redirect calls
`route('login')`, the verified middleware resolves `verification.notice`, and the password-reset and
email-verification notifications build their emailed links from `password.reset` and
`verification.verify`. This package registers a missing-named-route resolver, which the URL generator
consults when a name is **not** found — so every caller keeps working, including third-party code,
and nothing is shadowed: a name that resolves normally never reaches it, so an application's own
`login` route still wins.

> `Route::has()` is the exception. It asks the route collection directly and answers `false` for a
> bare name. Code guarding a link with it must ask for the scoped name;
> `AuthPreset::routeName('login')` builds it whatever the prefix is set to.

## Feature gates

Every feature gate removes its own routes rather than merely hiding a link. Web route/page gates are: login, registration, logout, password reset, profile updates, password updates, email verification, social login, and passkeys. Disabling a feature after publishing views can leave broken links, so update the view navigation and run `php artisan route:list` for both route modes.

For the exact API surface and response expectations, see [API routes](api-routes.md). To replace route behavior or presentation, see [customization](customization.md).

---

[← Docs index](../README.md#documentation)
