# Security

The preset supplies authentication routes, not application authorization. Use HTTPS, secure session cookies, CSRF protection, trusted hosts/proxies, a working mail transport, and application middleware such as `auth`, `verified`, or Sanctum abilities on protected application pages. The package `/dashboard` demonstrates an authenticated route only; it is not a safe authorization boundary for an application.

## Package controls

- `AUTHKIT_PRESET_GUARD` must identify the intended user provider; verify the provider/model relationship before enabling registration, session login, or profile changes.
- Guest web login, registration, and password-reset submissions have `throttle:10,1`; verification actions use `throttle:6,1`; the API group defaults to `throttle:60,1`. Auth Kit adds a separate credential limiter for login.
- `Features::botProtection()` validates CAPTCHA server-side on guest web submissions, but not API routes. See [bot protection](bot-protection.md).
- Successful web login regenerates the session; web logout invalidates it and regenerates the CSRF token. Password resets and updates revoke personal tokens when the model supports them.
- When using the optional social package, treat provider credentials and linked-account records as sensitive. Follow its verified-email linking rules in the [social login guide](https://github.com/laranail/authkit-social-login/blob/main/docs/social-login.md).

## Deployment review

Set the correct web/API prefixes before registering OAuth callbacks, and check the actual routes and middleware after every feature or route-mode change. Treat bearer tokens as secrets: return them only over TLS, do not log them, and revoke them with the API logout route. Confirm the public host, signed URLs, and mail links for password reset and verification. Passkeys require a matching relying-party ID, origin, and TLS configuration.

Review authkit's [security guidance](../../authkit/docs/security.md) for the underlying action behavior, token revocation, and passkey requirements.

---

[← Docs index](../README.md#documentation)
