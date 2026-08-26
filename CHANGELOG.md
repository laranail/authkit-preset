# Changelog

All notable changes to `laranail/authkit-preset` are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
  `laranail-authkit-preset::messages`, publishable to `lang/vendor/laranail-authkit-preset`.

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
