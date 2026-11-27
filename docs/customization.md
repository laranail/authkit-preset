# Customization

Publish only the resources the application needs to alter:

```bash
php artisan vendor:publish --tag=laranail::authkit-preset-config
php artisan vendor:publish --tag=laranail::authkit-preset-routes
php artisan vendor:publish --tag=laranail::authkit-preset-views
```

The route tag writes `routes/laranail-authkit-preset-web.php` and its companion `routes/laranail-authkit-preset-web-mount.php`. Keep both files together; require only the entry file. The core owns the API routes. Set `laranail.authkit-preset.routes.mode` to `published`, then require the published files from the application's route bootstrap; otherwise the provider intentionally does not load them. Load the published web file once; do not also load the package copy. Views are published to `resources/views/vendor/laranail/authkit-preset`, with pages in `blade/` and shared components in `components/`. This preserves the `blade.login` and component paths Laravel actually resolves.

## Upgrade published forms

Back up application-owned changes before republishing. Older releases copied pages directly into
the override root, although Laravel renders `blade.<page>`; those copies were not being used.
Move customized pages into `blade/` and merge the current templates, including the new profile
pending-status controls and `verify-email-change.blade.php`. Publish the view tag to obtain both
pages and components. Use `--force` only when you intend to replace existing application copies.
If routes were published previously, republish/merge both route files and require the entry once.

The profile and email-change forms use `AuthPreset::routeNameForCurrentRoute()` so their actions
stay on the signed-in guard's mount. Preserve that behavior in custom forms and redirects. Test
verification from the delivered email through the CSRF-protected confirmation form, and test
resend, cancellation, expired links and addresses claimed by another account.

Use `AUTHKIT_PRESET_AFTER_LOGIN`, `AUTHKIT_PRESET_AFTER_REGISTRATION`, and `AUTHKIT_PRESET_AFTER_LOGOUT` for simple redirect changes. Social routes and their redirect settings belong to `laranail/authkit-social-login`. Publish views for preset UI changes, and publish routes/controllers when preset behavior, middleware, response format, validation, token abilities, CAPTCHA policy, or authorization policy changes.

The shipped Blade views refer to vendor-scoped routes such as `laranail-auth.login`, `laranail-auth.register`, `laranail-auth.password.request`, `laranail-auth.logout`, and the passkey routes. Preserve those names or update all links, forms, JavaScript data attributes, and tests after renaming them. A published guest login, registration, or reset form must keep its CSRF token and include the `captcha` input when bot protection is enabled. After customisation, clear configuration/view caches as appropriate and run `php artisan route:list` to confirm that the app owns the expected routes.

---

[← Docs index](../README.md#documentation)
