# Environment variables

These names are read by this package's shipped configuration. Set deployment values in the host
application's environment; do not edit files under `vendor/`. Defaults and corresponding config
keys are explained in [Configuration](configuration.md).

| Variable |
| --- |
| `AUTHKIT_PRESET_STACK` |
| `AUTHKIT_PRESET_ROUTES_MODE` |
| `AUTHKIT_PRESET_GUARD` |
| `AUTHKIT_PRESET_ROUTE_NAME_PREFIX` |
| `AUTHKIT_PRESET_WEB_PREFIX` |
| `CAPTCHA_PROVIDER` |
| `AUTHKIT_PRESET_AFTER_LOGIN` |
| `AUTHKIT_PRESET_AFTER_REGISTRATION` |
| `AUTHKIT_PRESET_AFTER_LOGOUT` |

## Apply changes

```bash
php artisan config:clear
php artisan route:list
```

Check the configured route prefix and guard, then rebuild the configuration cache as part of your
deployment. Never commit filled provider-secret or signing-key variables. A variable mentioned by
a different package does not configure this package unless explicitly mapped here.

---

[← Docs index](../README.md#documentation)
