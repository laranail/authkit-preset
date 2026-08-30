# Connected accounts

Listing the social providers linked to a user, and removing one.

`GET /auth/user/social-accounts` renders the list; `DELETE /auth/user/social-accounts/{provider}`
removes a link. Both sit behind the configured guard, under the configured web prefix.

## The rule that governs removal

The last remaining link **cannot** be removed. It is the one social operation that can lock someone
out permanently, and the obvious guard against that does not work.

"Allow it when the user has a password" reads correctly and fails in the dangerous direction.
`laranail/authkit-social` provisions a social account with `Hash::make(Str::random(32))` — a password
the user has never seen and can never type — and Laravel's schema makes `users.password` NOT NULL, so
the column is populated for exactly the accounts most at risk. Nothing in a hash distinguishes a
chosen password from a generated one, so the package does not guess.

A user who wants to remove their only provider adds another sign-in method first. They hold a
verified address, so the ordinary password-reset flow is open to them.

An application that records whether a password was actually chosen can answer the question this
package cannot, and lift the restriction:

```php
// config/laranail/authkit-social.php
'unlink' => ['trust_password_column' => true],
```

## Why the control is disabled rather than refused

The view asks `SocialAccountService::canUnlink()` before rendering, so the only sign-in method shows
as unavailable with a reason instead of offering an action that then fails. Preventing the mistake is
better than reporting it afterwards.

## A provider that is no longer installed

A sub-package can be removed while its rows remain. Such a link still appears in the list and can
still be removed — `laranail/authkit-social` reads an unresolvable slug back as a plain string rather
than throwing, so one retired provider does not make the whole account unreadable.

---

[← Docs index](../README.md#documentation)
