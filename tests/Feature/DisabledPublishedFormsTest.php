<?php

declare(strict_types=1);

use Workbench\App\Models\User;
use Simtabi\Laranail\AuthKit\Preset\Features;

trait DisabledPublishedFormsEnvironment
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('laranail.authkit-preset.features', [Features::login(), Features::logout()]);
    }
}

uses(DisabledPublishedFormsEnvironment::class, Tests\Concerns\UsesPublishedPresetViews::class);
beforeEach(fn () => $this->publishPresetViews());
afterEach(fn () => $this->removePublishedPresetViews());

it('hides disabled features in the published login and navigation and omits their routes', function (): void {
    $this->get(route('login'))->assertOk()->assertDontSee('Sign in with a passkey')->assertDontSee('Forgot password?')->assertDontSee('Create account');
    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()
        ->assertDontSee('>Profile<', false)->assertDontSee('>Passkeys<', false)->assertDontSee('>Two-factor<', false);
    foreach (['user-profile-information.edit', 'user-email-change.verify', 'user-email-change.confirm', 'user-email-change.resend', 'user-email-change.destroy', 'two-factor.challenge', 'register', 'password.request', 'passkey.login'] as $name) {
        expect(app('router')->has('laranail-auth.' . $name))->toBeFalse();
    }
    $this->get('/auth/user/profile-information')->assertNotFound();
    $this->post('/auth/user/email-change/verify')->assertNotFound();
});
