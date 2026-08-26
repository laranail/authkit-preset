# Architecture

What belongs in this package, what belongs in the core, and how the two meet.

## The family

```
laranail/authkit           Simtabi\Laranail\AuthKit\           headless core + REST API
laranail/authkit-preset    Simtabi\Laranail\AuthKit\Preset\    this package
laranail/authkit-sso       Simtabi\Laranail\AuthKit\Sso\       SAML and OIDC
laranail/authkit-oauth     Simtabi\Laranail\AuthKit\OAuth\     OAuth and social identity
laranail/authkit-tenancy   Simtabi\Laranail\AuthKit\Tenancy\   multi-tenancy
laranail/authkit-ldap      Simtabi\Laranail\AuthKit\Ldap\      LDAP and Active Directory
```

One root namespace, each sibling a segment under it. Composer's loader matches the longest prefix
first, so `Simtabi\Laranail\AuthKit\Preset\` resolves here and everything shallower resolves into
the core.

## The dividing line

**This package is scaffolding. It should hold no business logic.**

| Belongs here | Belongs in the core |
|---|---|
| Blade views, components, layouts | Credential verification |
| Web routes and the route groups | Identity resolution and linking |
| Thin controllers that resolve and delegate | Token issuance and revocation |
| The installer | Rate limiting |
| Translations | Validation rules |

The test is simple: could an API-only or Filament application, with no Blade at all, get the same
behaviour by calling the core directly? If not, the logic is in the wrong package.

Being honest about the current state: it is not fully held. The API controllers here mint and revoke
Sanctum tokens directly, and the captcha middleware builds its own validator. Both should move down
into core actions this package calls. Tracked as work, not a design.

## How a controller should read

```php
class LoginController extends AbstractAttemptEmailPasswordLoginController
{
    public function create(): View
    {
        return view(AuthPreset::view('login'));
    }
}
```

The base class in the core owns the flow; this class owns the rendering. Overriding `passed()`,
`failed()` or `throttled()` changes the response without touching the flow.

## Public names

Every name this package registers is vendor-scoped, because Laravel keeps these in flat global maps
where a second claimant silently replaces the first:

| Surface | Value |
|---|---|
| View namespace | `laranail-authkit-preset` |
| Blade components | `<x-laranail-authkit-preset::…>` |
| Translation namespace | `laranail-authkit-preset` |
| Config key | `laranail.authkit-preset` |
| Publish tags | `laranail::authkit-preset-{config,routes,views,lang}` |
| Artisan command | `laranail::authkit-preset.install` |

`tests/Feature/NamingConventionTest.php` asserts these against the **live registries** on a booted
application rather than the provider source, so the guard survives a refactor of the registration
code. Grepping the provider proves how a registration was written; it does not prove what the
framework ended up holding.

## Frontend stacks

`FrontendStack` currently has one case, `Blade`. Adding React or Vue is meant to be a new case plus
component stubs plus the token API — not a rewrite — which is why the enum and the installer are
shaped around it even while only one stack exists.

---

[← Docs index](../README.md#documentation)
