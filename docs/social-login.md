# Social login integration

Social login is an optional package. The recommended setup is to install it beside the preset:

```bash
composer require laranail/authkit-social-login
php artisan laranail::authkit-social-login.install --social=google
php artisan migrate
```

The preset renders the social package's namespaced button component on its login and registration
pages through its optional `<x-laranail-authkit-preset::social-buttons />` wrapper. The wrapper safely
renders empty when the social package is absent.

> `<x-authkit-social-buttons />` is the wrapper's former, bare name. It is a deprecated alias: it
> still renders the same thing, so a view published before the rename keeps working, and raises one
> `E_USER_DEPRECATED` notice per process. Replace it in published views; it may stop resolving in the
> next minor after 0.1. A button is shown only when its provider is enabled and has a
client ID configured; see the social package's [configuration guide](https://github.com/laranail/authkit-social-login/blob/main/docs/configuration.md).

The social package owns provider configuration, credentials, web and API routes, callbacks, linked
account management, buttons, icons, and migrations. When both packages are installed, its web routes
inherit the preset's prefixes, guards, middleware, route-name prefixes, and configured guard mounts.
The social package also works without the preset; see its [installation guide](https://github.com/laranail/authkit-social-login/blob/main/docs/installation.md),
[social login guide](https://github.com/laranail/authkit-social-login/blob/main/docs/social-login.md),
and [connected accounts guide](https://github.com/laranail/authkit-social-login/blob/main/docs/connected-accounts.md).

---

[← Docs index](../README.md#documentation)
