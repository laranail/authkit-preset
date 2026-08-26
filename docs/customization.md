# Customization

Publish only the resources the application needs to alter:

```bash
php artisan vendor:publish --tag=laranail::authkit-preset-config
php artisan vendor:publish --tag=laranail::authkit-preset-routes
php artisan vendor:publish --tag=laranail::authkit-preset-views
```

The route tag writes `routes/laranail-authkit-preset-web.php` and `routes/laranail-authkit-preset-api.php`. Set `laranail.authkit-preset.routes.mode` to `published`, then require the published files from the application's route bootstrap; otherwise the provider intentionally does not load them. This is an all-or-nothing route ownership change: publish both files and load only the route surfaces the application intends to expose. Views are published to `resources/views/vendor/laranail-authkit-preset`, where they can be edited without modifying a dependency.

Use `AUTHKIT_PRESET_AFTER_LOGIN`, `AUTHKIT_PRESET_AFTER_REGISTRATION`, `AUTHKIT_PRESET_AFTER_LOGOUT`, and `AUTHKIT_PRESET_AFTER_SOCIAL_LOGIN` for simple redirect changes. Publish views for UI changes, and publish routes/controllers when behavior, middleware, response format, validation, token abilities, CAPTCHA policy, or authorization policy changes.

The shipped Blade views refer to named routes such as `login`, `register`, `password.request`, `logout`, and the passkey routes. Preserve those names or update all links, forms, JavaScript data attributes, and tests after renaming them. A published guest login, registration, or reset form must keep its CSRF token and include the `captcha` input when bot protection is enabled. After customisation, clear configuration/view caches as appropriate and run `php artisan route:list` to confirm that the app owns the expected routes.

---

[← Docs index](../README.md#documentation)
