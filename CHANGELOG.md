# Changelog

All notable changes to `laranail/authkit-preset` are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- **The Twitter button is now X**, matching `laranail/authkit-social`'s slug change from `twitter`
  to `x`. Breaking for any application listing `twitter` in
  `laranail.authkit-preset.social.providers`: the button renders nothing and the route returns 404.


### Added

- **Apple sign-in support.** An Apple button in the social-buttons component, `apple` accepted by
  `--social=`, and — the part that is not cosmetic — the `social.callback` route now accepts **POST**
  as well as GET and is excluded from CSRF.

  Apple requests the `name` and `email` scopes, which forces `response_mode=form_post`, so Apple POSTs
  the callback from its own servers with no session and no CSRF token. The previous GET-only route
  answered 405. The exclusion names `PreventRequestForgery` because that is what Laravel's `web` group
  actually registers; `VerifyCsrfToken` and `ValidateCsrfToken` are deprecated subclasses and
  excluding either would silently do nothing.

### Removed

- **The Facebook social button and provider key.** `laranail/authkit-social` no longer ships
  `SocialProvider::FACEBOOK` — Facebook asserts no email-verification claim, so it could never sign
  in — and the button, the `--social=` help text, and the documented provider list follow it.
  Breaking for any application listing `facebook` in `laranail.authkit-preset.social.providers`:
  remove it, or the login page renders a button for a provider that returns a 404.


### Changed

- The PHP floor is `^8.4.1`, up from `^8.4`. `laranail/package-tools` and `laranail/console`
  are `^8.4.1`, so a resolver that took the manifest at its word and pinned the platform to
  8.4.0 could not install them. Dependabot does exactly that, and had been failing on it.

- **Breaking.** The view and translation namespaces are now the composer package name,
  `laranail/authkit-preset`, so `view('laranail/authkit-preset::blade.login')` and
  `__('laranail/authkit-preset::messages.login.title')` name the package that ships the file.
  Published files follow into `resources/views/vendor/laranail/authkit-preset` and
  `lang/vendor/laranail/authkit-preset`, which is where Laravel reads them from.

  **Blade component tags keep the hyphen** — `<x-laranail-authkit-preset::layout />` — because
  Blade's tag parser admits no forward slash and would truncate the prefix at `laranail`, rendering
  the tag as literal text with no error. The provider registers that prefix as an alias over the
  same resolved paths, published override directory included, so both spellings find the same file.

### Changed

- **Breaking.** Renamed from `laranail/auth-preset` to
  `laranail/authkit-preset`, and the namespace moved to `Simtabi\Laranail\AuthKit\Preset\`. The family now shares one root
  namespace with each sibling as a segment under it.
- **Breaking.** Every public name is vendor-scoped. Laravel keeps these in flat global maps, where
  a second package claiming the same key silently replaces the first:

| Surface | Before | After |
|---|---|---|
| Config key | `auth-preset` | `laranail.authkit-preset` |
| Config file | `config/auth-preset.php` | `config/laranail/authkit-preset.php` |
| Publish tags | `auth-preset-config`, … | `laranail::authkit-preset-*` |
| Env prefix | `AUTH_PRESET_*` | `AUTHKIT_PRESET_*` |
| View namespace | `auth-preset` | `laranail-authkit-preset` |
| Blade components | `<x-auth-preset::…>` | `<x-laranail-authkit-preset::…>` |
| Artisan command | `laranail:authkit.install` | `laranail::authkit-preset.install` |

### Added

- A `NamingConventionTest` that asserts the public names against the **live registries** on a booted
  application, rather than the provider source, so the guard survives a refactor.
- A translation namespace. The package previously shipped none, so every user-facing string was
  hardcoded English with no override point. 67 keys now live under
  `laranail/authkit-preset::messages`, publishable to `lang/vendor/laranail-authkit-preset`.

### Fixed

- The installer wrote a Tailwind `@source` glob pointing at `vendor/laravel/laranail`, a path that
  never existed. It matched zero files, so a clean asset build purged every utility class the auth
  views use and the pages shipped unstyled.
- `addModelInterface()` returned `implode('', $matches)`, which emits the full match *and* all three
  capture groups — running the installer twice duplicated the class declaration and left the
  application's `User` model a syntax error.
- Migration publishing was not idempotent. `vendor:publish` re-stamps a fresh timestamp on every
  run, so a second install left two files declaring the same table and `migrate` died.

### Removed

- `composer.lock` is no longer tracked. A library's lock records a resolution consumers never use.
