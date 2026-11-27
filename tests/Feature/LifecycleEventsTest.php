<?php

declare(strict_types=1);

use Workbench\App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Simtabi\Laranail\AuthKit\Preset\Events;
use Simtabi\Laranail\Package\Tools\Testing\TestCredentials;
use Simtabi\Laranail\AuthKit\Services\TwoFactorAuthentication;

trait LifecycleChallengeEnvironment
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('laranail.authkit.two_factor.enabled', true);
        $app['config']->set('laranail.authkit-preset.features', [...$app['config']->get('laranail.authkit-preset.features'), 'two-factor-authentication']);
    }
}

uses(LifecycleChallengeEnvironment::class);

it('reports pending, refused and completed browser challenges without codes', function (): void {
    Schema::table('users', fn (Blueprint $table) => $table->string('two_factor_method')->default('none'));
    $password = TestCredentials::password('login');
    $user = User::factory()->create(['password' => $password]);
    $factor = app(TwoFactorAuthentication::class);
    $setup = $factor->begin($user);
    $codes = $factor->confirm($user, (new Google2FA)->getCurrentOtp($setup['secret']));
    cache()->flush();
    Event::fake([Events\TwoFactorChallengeStarted::class, Events\TwoFactorChallengeFailed::class, Events\TwoFactorChallengeCompleted::class]);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => $password])->assertRedirect();
    Event::assertDispatchedTimes(Events\TwoFactorChallengeStarted::class, 1);
    Event::assertNotDispatched(Events\TwoFactorChallengeCompleted::class);
    $this->assertGuest();
    $this->post(route('two-factor.challenge.verify'), ['code' => 'invalidcode-invalidco'])->assertSessionHasErrors('code');
    Event::assertDispatchedTimes(Events\TwoFactorChallengeFailed::class, 1);
    Event::assertNotDispatched(Events\TwoFactorChallengeCompleted::class);
    $this->post(route('two-factor.challenge.verify'), ['code' => $codes[0]])->assertRedirect();
    $this->assertAuthenticatedAs($user);
    Event::assertDispatchedTimes(Events\TwoFactorChallengeCompleted::class, 1);
    Event::assertDispatched(Events\TwoFactorChallengeCompleted::class, fn ($event) => $event->userId === (string) $user->getKey()
        && $event->guard === 'web' && ! str_contains(json_encode($event), $codes[0]));
});

it('does not announce a challenge for password-only login or an invalid pending session', function (): void {
    $user = User::factory()->create(['password' => TestCredentials::password('login')]);
    Event::fake([Events\TwoFactorChallengeStarted::class, Events\TwoFactorChallengeCompleted::class]);
    $this->post(route('login.store'), ['email' => $user->email, 'password' => TestCredentials::password('login')])->assertRedirect();
    Event::assertNotDispatched(Events\TwoFactorChallengeStarted::class);
    auth()->logout();
    $this->post(route('two-factor.challenge.verify'), ['code' => '123456'])->assertRedirect();
    Event::assertNotDispatched(Events\TwoFactorChallengeCompleted::class);
});
