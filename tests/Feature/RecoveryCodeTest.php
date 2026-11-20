<?php

declare(strict_types=1);

use Workbench\App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Simtabi\Laranail\AuthKit\Services\TwoFactorAuthentication;

trait RecoveryCodeEnvironment
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('laranail.authkit.two_factor.enabled', true);
        $app['config']->set('laranail.authkit-preset.features', [...$app['config']->get('laranail.authkit-preset.features'), 'two-factor-authentication']);
    }
}

uses(RecoveryCodeEnvironment::class);

beforeEach(function (): void {
    Schema::table('users', function (Blueprint $table): void {
        $table->string('two_factor_method')->default('none');
    });

    $this->user = User::factory()->create();
    $this->twoFactor = app(TwoFactorAuthentication::class);
    $setup = $this->twoFactor->begin($this->user);
    $this->secret = $setup['secret'];
    $codes = $this->twoFactor->confirm($this->user, (new Google2FA)->getCurrentOtp($this->secret));
    cache()->flush();
    $this->code = $codes[0];
    expect(strlen($this->code))->toBe(21);
});

function pendingRecoveryChallenge($test): void
{
    $test->withSession(['authkit.two_factor_pending' => ['user_id' => $test->user->id, 'guard' => 'web']]);
}

it('accepts a full recovery code for login and rejects reuse in a fresh challenge', function (): void {
    pendingRecoveryChallenge($this);
    $this->post(route('two-factor.challenge.verify'), ['code' => $this->code])->assertRedirect()->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($this->user);
    expect($this->twoFactor->recoveryCodes($this->user->fresh()))->not->toContain($this->code);

    auth()->logout();
    pendingRecoveryChallenge($this);
    $this->post(route('two-factor.challenge.verify'), ['code' => $this->code])->assertSessionHasErrors('code');
    $this->assertGuest();
});

it('rejects an invalid recovery code without signing in', function (): void {
    pendingRecoveryChallenge($this);
    $this->post(route('two-factor.challenge.verify'), ['code' => 'invalidcode-invalidco'])->assertSessionHasErrors('code');
    $this->assertGuest();
});

it('still accepts a six-digit authenticator code', function (): void {
    pendingRecoveryChallenge($this);
    $this->post(route('two-factor.challenge.verify'), ['code' => (new Google2FA)->getCurrentOtp($this->secret)])->assertRedirect()->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($this->user);
});

it('renders a text keyboard for recovery codes', function (): void {
    pendingRecoveryChallenge($this);
    $this->get(route('two-factor.challenge'))->assertOk()->assertSee('inputmode="text"', false)->assertDontSee('inputmode="numeric"', false);
});

it('accepts a recovery code when disabling two-factor authentication', function (): void {
    $this->actingAs($this->user)->withSession(['auth.password_confirmed_at' => time()])->post(route('user-two-factor.disable'), ['code' => $this->code])->assertRedirect()->assertSessionHasNoErrors();
    expect($this->twoFactor->enabled($this->user->fresh()))->toBeFalse();
});

it('accepts a recovery code when regenerating codes', function (): void {
    $this->actingAs($this->user)->withSession(['auth.password_confirmed_at' => time()])->post(route('user-two-factor.recovery-codes'), ['code' => $this->code])->assertOk();
    expect($this->twoFactor->recoveryCodes($this->user->fresh()))->toHaveCount(8)->not->toContain($this->code);
});
