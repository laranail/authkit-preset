# API routes

The REST API moved to `laranail/authkit`, the headless core, so an API-only or Filament consumer
gets it without installing this package's Blade scaffolding.

See [the API routes guide in `laranail/authkit`](https://github.com/laranail/authkit/blob/main/docs/api-routes.md).

This package still installs what the API needs when you run its installer with `--api`: Sanctum's
`HasApiTokens` trait on the authenticatable model, and the `personal_access_tokens` migration.

---

[← Docs index](../README.md#documentation)
