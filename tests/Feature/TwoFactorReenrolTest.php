<?php

declare(strict_types=1);

use Workbench\App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Simtabi\Laranail\AuthKit\Services\TwoFactorAuthentication;

trait TwoFactorReenrolEnvironment
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

uses(TwoFactorReenrolEnvironment::class);

beforeEach(function (): void {
    Schema::table('users', function (Blueprint $table): void {
        $table->string('two_factor_method')->default('none');
    });

    $this->user = User::factory()->create();
});

// `begin()` declared `: View` while returning a redirect on the already-enrolled branch, so this
// posted a TypeError rather than sending the user to their two-factor page. Reaching it needs no
// trickery: enrol, then submit the enrol form again, which is what a double submit or a back
// button does.
it('redirects instead of fataling when two-factor is already enabled', function (): void {
    $twoFactor = app(TwoFactorAuthentication::class);
    $setup = $twoFactor->begin($this->user);
    $twoFactor->confirm($this->user, (new Google2FA)->getCurrentOtp($setup['secret']));

    expect($twoFactor->enabled($this->user->fresh()))->toBeTrue();

    $this->actingAs($this->user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('user-two-factor.begin'))
        ->assertRedirect(route('user-two-factor.index'));
});

// The first enrolment must still render the setup view, so the widened return type cannot be
// satisfied by always redirecting.
it('still renders the setup view on a first enrolment', function (): void {
    $this->actingAs($this->user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('user-two-factor.begin'))
        ->assertOk();
});
