# Release

How a version is cut, and what a release has to carry.

## Versioning

Semantic versioning. While pre-1.0 the minor position carries breaking changes, so consumers pin
with `^0.1`.

`composer.json` declares `branch-alias` `dev-main → 0.1.x-dev`, so a path or dev checkout satisfies
a `^0.1` constraint during development.

## Ordering against the core

This package depends on `laranail/authkit`. When a change spans both, **the core is tagged first**.

This is not a style preference — Composer derives a VCS repository's package name from its *default
branch*, so a core change that lives only on a branch is invisible under its new name and a
dependent package simply cannot resolve it. Tag the core, then the preset.

## Before tagging

- CI green on `main` — tests and style.
- The naming guard passing. That is what proves no public name regressed to a bare form.
- A `CHANGELOG.md` section written for someone deciding whether to upgrade.
- Verified in a real application: the demo at `laranail/demos/authkit` installs this package the way
  a consumer does, runs the installer, and boots. A green package suite is not sufficient — it runs
  in an isolated Testbench app, which is where integration failures hide.

## Cutting the release

```bash
git tag vX.Y.Z
git push origin vX.Y.Z
```

The release body is the CHANGELOG section for that version. A release with auto-generated notes or
a "see CHANGELOG" stub is incomplete.

## Resolution

`laranail/*` packages resolve through git, not Packagist. Consumers add the repositories block from
[installation](installation.md); nothing is pushed to Packagist as part of a release.

---

[← Docs index](../README.md#documentation)
