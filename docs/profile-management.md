# Profile management

Keep `Features::updateProfileInformation()` enabled to register the profile and email-change
browser routes. They use the configured web middleware and `auth:<guard>` on each mount.

| Method and default path | Behavior |
| --- | --- |
| `GET /auth/user/profile-information` | Show the active email and any pending change, its expiry, resend and cancel controls. |
| `PUT /auth/user/profile-information` | Save the required name; a different unique email starts a verified change. |
| `GET /auth/user/email-change/verify?token=…` | Display the confirmation form after signing in. Does not consume the link. |
| `POST /auth/user/email-change/verify` | Confirm the proof and apply the new verified address. |
| `POST /auth/user/email-change/resend` | Send a fresh proof to the pending address, invalidating the previous proof. |
| `DELETE /auth/user/email-change` | Cancel the pending change and keep the active address. |

The current email and verification state stay active until the new address is proved. The form
shows the active address in the email field and the pending address separately. Saving an unchanged
email while editing the name leaves an existing pending change intact. To replace the pending
address, submit another new address; to discard it, use Cancel email change.

AuthKit owns pending storage, normalization, duplicate checks, expiry and single-use proof
verification. Addresses are checked again at confirmation in case another account claimed one
while the change was pending. Invalid, expired, cancelled, superseded and reused proofs display
errors on the profile page and cannot change the address. Expired requests require a new profile
submission. The confirmation form posts with CSRF protection; the page suppresses referrers and
authenticated page caching.

Profile updates, verification, resend and cancel share a six-request-per-minute throttle. Removing
the profile feature removes all its browser routes and navigation. The current-address verification
feature remains separate; disabling that feature does not disable proof of a new address.

## Backend and mail setup

This flow requires the verified-email-change backend merged in
[AuthKit #98](https://github.com/laranail/authkit/pull/98). The dependency accepts AuthKit `^0.1.3`
or `dev-main`; until the next stable tag includes it, Composer resolves `dev-main`.
Configure Laravel mail delivery and a persistent shared cache, as described in the
[core profile guide](https://github.com/laranail/authkit/blob/main/docs/profile-management.md).
No application migration is needed for pending email changes.

The preset supplies browser verification links automatically, including the current guard's mount,
without changing shared core configuration. Browser changes work with the core API disabled.
An explicit `laranail.authkit.email_change.verification_url` still takes precedence for applications
that supply their own client. Core `email_change.expires_after_minutes` defaults to `60`.

Published profile views live under `resources/views/vendor/laranail/authkit-preset/blade/`.
Keep the CSRF fields, method overrides, named error bags and active-guard route names when
customizing them. Profile action errors use `updateProfileInformation`; confirmation/resend errors
use `emailChange`. Status and pending-state messages are in the package's `messages.email_change`
translations. See [customization](customization.md) for upgrading existing published copies.

The API equivalent remains owned by AuthKit. It uses accepted bearer-token guards and does not
render the preset pages. See [route configuration](route-configuration.md),
[core profile management](https://github.com/laranail/authkit/blob/main/docs/profile-management.md)
and [password updates](password-updates.md).

---

[← Docs index](../README.md#documentation)
