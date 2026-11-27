# Lifecycle events and audit hooks

`laranail/authkit-preset` dispatches the public events below through Laravel's normal event dispatcher.
Classes live under `Simtabi\Laranail\AuthKit\Preset\Events`. Payloads are immutable metadata snapshots: scalar IDs,
booleans and, for OAuth consent, scope-name lists. They contain no model/request objects,
passwords, bearer/provider tokens, authorization codes, recovery values or client secrets.

## Event catalog

| Event | Public payload | Dispatch point |
| --- | --- | --- |
| `TwoFactorChallengeCompleted` | `userType: string`, `userId: string`, `guard: string` | After factor verification, browser login, session rotation and the verified-session marker. |
| `TwoFactorChallengeFailed` | `userType: string`, `userId: string`, `guard: string` | A pending challenge for an existing user fails factor verification. Malformed requests or absent pending sessions do not emit it. |
| `TwoFactorChallengeStarted` | `userType: string`, `userId: string`, `guard: string` | After the browser session holds a pending challenge. Password proof is accepted but the user is not yet signed in. |

`userType`/`actorType` are the authenticatable PHP class names; IDs are strings so UUID and integer
models share one contract. Nullable fields are marked `?`. These are public payloads; listeners
should use the named properties rather than infer a meaning from dispatch order.

## Register a listener

In the host application's service provider `boot()` method:

```php
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Simtabi\Laranail\AuthKit\Preset\Events\TwoFactorChallengeCompleted;

Event::listen(TwoFactorChallengeCompleted::class, function (TwoFactorChallengeCompleted $event): void {
    Log::info('authkit.TwoFactorChallengeCompleted', get_object_vars($event));
});
```

Use your application's audit channel or listener class when it needs persistence or another sink.
Normal Laravel event discovery and class listeners work too. The package provides extension hooks,
not an audit table, retention policy or external logging service.

## Avoid duplicate account audits

The preset emits only its own browser challenge stages. Registration, password/profile changes,
token operations and factor setup/disable/rotation are owned by the core actions/services; see the
[core event catalog](https://github.com/laranail/authkit/blob/main/docs/events.md). Use a core release
containing those events when subscribing to them. Ordinary password-only login emits no preset
challenge event. API factor challenges belong to the core and are outside this preset catalog.

A started challenge is not a completed sign-in. Completion is published only after the browser
session is authenticated and marked factor-verified. Invalid input, a missing pending session or a
missing account does not claim completion.

## Listener execution and transactions

Dispatch is synchronous after the operation described above completes. Listener exceptions
propagate; a failing listener can interrupt the response after a write has already happened. Keep
authentication-path listeners small and reliable, or queue expensive work.

Events do not start or commit a surrounding application transaction. If an application wraps an
operation in a transaction, its audit consumer must respect commit/rollback (for example, queue
listeners with the host's after-commit configuration). A synchronous event is not a durable
outbox or proof that a caller's later transaction committed. No listener can veto an operation
by returning `false`; replace the documented action/contract for pre-operation policy.

## Verification

The normal test suite covers payload contracts, successful dispatch counts and rejection paths.
For a focused check:

```bash
XDEBUG_MODE=off vendor/bin/pest --colors=never --no-coverage tests/Architecture/EventPayloadTest.php tests/Feature/LifecycleEventsTest.php
```

OAuth's authorization/device tests additionally cover approve, deny and invalid session tokens.
Social's deliberate-connect tests additionally cover idempotent and refused linking.

---

[← Docs index](../README.md#documentation)
