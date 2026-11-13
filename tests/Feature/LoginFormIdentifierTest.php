<?php

declare(strict_types=1);

use Workbench\App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Simtabi\Laranail\Package\Tools\Testing\TestCredentials;

/*
 * authkit v0.1.1 made the login identifier configurable: `laranail.authkit.login.field` names the
 * input the request reads, and `AttemptEmailPasswordLoginRequest` drops its `email` rule as soon as
 * more than one identifier is configured. The preset's form did not follow, so the feature shipped
 * and stayed unreachable for every preset consumer.
 *
 * These assert the form against the live configuration rather than against a fixed string, so the
 * form cannot describe a mode the backend does not implement, or vice versa.
 */

function loginHtml(): string
{
    return test()->get(route('login'))->assertOk()->getContent();
}

/* ------------------------------------------------------------------ the default, unchanged */

it('renders an email field when email is the only identifier', function (): void {
    $html = loginHtml();

    expect($html)->toContain('name="email"')
        ->and($html)->toContain('type="email"')
        ->and($html)->toContain('autocomplete="email webauthn"')
        ->and($html)->toContain('Email address')
        ->and($html)->not->toContain('Email or username');
});

/* ------------------------------------------------- the form follows a wider identifier set */

it('stops typing the field as an address once a username can be used', function (): void {
    config()->set('laranail.authkit.login.identifiers', ['email', 'username']);

    $html = loginHtml();

    // type="email" is the part that matters most: the browser rejects a username before the
    // request is ever made, so the backend's widened rules are never reached.
    expect($html)->toContain('type="text"')
        ->and($html)->not->toContain('type="email"')
        ->and($html)->toContain('autocomplete="username webauthn"')
        ->and($html)->toContain('Email or username');
});

it('renames the input when the configured field is renamed', function (): void {
    config()->set('laranail.authkit.login.field', 'username');
    config()->set('laranail.authkit.login.identifiers', ['username']);

    $html = loginHtml();

    expect($html)->toContain('name="username"')
        ->and($html)->toContain('id="username"')
        ->and($html)->not->toContain('name="email"');
});

/* ------------------------------------------------------- the point: the feature is reachable */

it('signs a user in with a username through the rendered form', function (): void {
    Schema::table('users', function (Blueprint $table): void {
        $table->string('username')->nullable()->unique();
    });

    config()->set('laranail.authkit.login.identifiers', ['email', 'username']);

    $password = TestCredentials::password();
    $user = User::factory()->create([
        'email'    => 'someone@example.com',
        'username' => 'someone',
        'password' => bcrypt($password),
    ]);

    // Read the field name OUT of the rendered form and post under that, so this test is coupled to
    // the form rather than to the config. Posting a hard-coded 'email' here passed even with the
    // form reverted to a hardcoded address field -- a test whose name says "through the rendered
    // form" while bypassing it is the kind that reports success for the wrong reason.
    preg_match('/<input[^>]*\bname="([^"]+)"[^>]*autocomplete="[^"]*webauthn/', loginHtml(), $m);
    expect($m[1] ?? null)->not->toBeNull('the login form rendered no identifier input');

    $this->post(route('login.store'), [
        $m[1]      => 'someone',
        'password' => $password,
    ])->assertRedirect();

    expect(auth()->check())->toBeTrue()
        ->and(auth()->id())->toBe($user->getAuthIdentifier());
});

/* --------------------------------------------------- nothing else changed its identifier field */

it('leaves the forms that genuinely want an address alone', function (): void {
    config()->set('laranail.authkit.login.identifiers', ['email', 'username']);

    foreach (['register', 'password.request'] as $name) {
        $html = test()->get(route($name))->assertOk()->getContent();

        expect($html)->toContain('type="email"')
            ->and($html)->toContain('name="email"');
    }
});
