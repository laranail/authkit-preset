<?php

declare(strict_types=1);

use Workbench\App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Simtabi\Laranail\Package\Tools\Testing\TestCredentials;
use Simtabi\Laranail\AuthKit\Services\TwoFactorAuthentication;

trait JsonLoginTwoFactorEnvironment
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('laranail.authkit.two_factor.enabled', true);
        $app['config']->set('laranail.authkit-preset.features', [
            ...$app['config']->get('laranail.authkit-preset.features'),
            'two-factor-authentication',
        ]);
    }
}

uses(JsonLoginTwoFactorEnvironment::class);

beforeEach(function (): void {
    Schema::table('users', function (Blueprint $table): void {
        $table->string('two_factor_method')->default('none');
    });

    $this->password = TestCredentials::password();
    $this->user = User::factory()->create([
        'email'    => 'ada@example.com',
        'password' => bcrypt($this->password),
    ]);
});

function enrolInTwoFactor(User $user): void
{
    $twoFactor = app(TwoFactorAuthentication::class);
    $setup = $twoFactor->begin($user);
    $twoFactor->confirm($user, (new Google2FA)->getCurrentOtp($setup['secret']));
    cache()->flush();
}

// The two-factor gate lives in `handlePassed()`. Once the core stopped short-circuiting on
// `expectsJson()`, a JSON caller reaches that gate — but it answered with a 302 to a browser page,
// which a fetch client cannot follow meaningfully. It needs to be told that a challenge is
// required, in the representation it asked for.
it('tells a JSON caller that a two-factor challenge is required', function (): void {
    enrolInTwoFactor($this->user);

    $this->postJson(route('login.store'), [
        'email'    => 'ada@example.com',
        'password' => $this->password,
    ])
        ->assertStatus(202)
        ->assertJsonPath('status', 'two-factor-required');

    // The whole point of the gate: credentials alone must not sign anyone in.
    $this->assertGuest();
    expect(session('authkit.two_factor_pending.user_id'))->toBe($this->user->getAuthIdentifier());
});

it('still redirects a form caller to the challenge page', function (): void {
    enrolInTwoFactor($this->user);

    $this->post(route('login.store'), [
        'email'    => 'ada@example.com',
        'password' => $this->password,
    ])->assertRedirect(route('two-factor.challenge'));

    $this->assertGuest();
});

// Without two-factor the JSON caller must actually be signed in, which is the defect the core
// change fixed. Asserted here too, through the real mounted route rather than a test double.
it('signs a JSON caller in when two-factor is not enabled', function (): void {
    $this->postJson(route('login.store'), [
        'email'    => 'ada@example.com',
        'password' => $this->password,
    ])->assertOk()->assertJsonPath('status', 'passed');

    $this->assertAuthenticated();
});
