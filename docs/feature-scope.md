# Feature scope

The preset is pre-1.0 and supports **Blade only**. No React, Vue, Livewire or other second stack is
provided. It depends on the AuthKit core and delegates authentication to it.

| Capability | Current scope |
| --- | --- |
| Login, registration, logout | Blade forms and configurable browser routes. |
| Password reset, updates and profile | Feature-gated forms using core actions. |
| Email verification | Verification of the current email; profile edits replace the address before verification. A pending verified-email-change flow is not implemented. |
| TOTP | Enrollment, confirmation and single-use alphanumeric recovery-code challenge. |
| Passkeys | Browser client and session routes; no bearer-token passkey API. |
| REST API | Owned by the core; `--api` prepares Sanctum on the selected model. |
| Social login | Optional social-login package; provider credentials and callbacks are owned there. |
| Bot protection | Optional captcha integration on supported guest forms. |
| Account deletion, lockout, password history | Not supplied by the preset. |
| Magic links | Deferred from 1.0. |

Publishing views creates application-owned copies. Review upstream changes when upgrading; a
published form does not update itself. Keep CSRF fields, route names, supported input formats and
CAPTCHA handling aligned with the backend. Recovery-code fields accept the full 21-character
alphanumeric value, including its separator; do not restrict them to numeric input.

See [Customization](customization.md) and the
[core feature scope](https://github.com/laranail/authkit/blob/main/docs/feature-scope.md).

Public audit hooks and their exact boundaries are documented in [Events](events.md).

---

[← Docs index](../README.md#documentation)
