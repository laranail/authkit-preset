<?php

declare(strict_types=1);

use Workbench\App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\AnonymousNotifiable;
use Simtabi\Laranail\AuthKit\Notifications\VerifyEmailChange;

trait EmailChangeMountEnvironment
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('laranail.authkit.api.enabled', false);
        $app['config']->set('auth.guards.admin', ['driver' => 'session', 'provider' => 'users']);
        $app['config']->set('laranail.authkit-preset.guards', ['admin' => ['prefix' => 'staff/auth', 'name' => 'admin.']]);
    }
}

uses(EmailChangeMountEnvironment::class, Tests\Concerns\UsesPublishedPresetViews::class);
beforeEach(function (): void {
    $this->publishPresetViews();
    Notification::fake();
});
afterEach(fn () => $this->removePublishedPresetViews());

it('keeps published forms mail links and redirects on the current guard without the API', function (): void {
    $primary = User::factory()->create();
    $admin = User::factory()->create();
    $primaryName = 'laranail-auth.user-profile-information.';
    $adminName = 'laranail-auth.admin.user-profile-information.';
    $this->actingAs($primary, 'web')->actingAs($admin, 'admin');
    $form = $this->presetForm($this->get(route($adminName . 'edit'))->assertOk()->getContent(), 'email');
    expect($form['action'])->toBe(route($adminName . 'update'));
    $this->{$form['method']}($form['action'], [...$form['fields'], 'email' => 'admin-new@example.test'])->assertRedirect()->assertSessionHasNoErrors();
    $notification = Notification::sent(new AnonymousNotifiable, VerifyEmailChange::class)->last();
    expect($notification->url)->toStartWith(route('laranail-auth.admin.user-email-change.verify') . '?token=');
    $adminUrl = $notification->url;
    $this->get(route($adminName . 'edit'))->assertSee(route('laranail-auth.admin.user-email-change.resend'))->assertSee(route('laranail-auth.admin.user-email-change.destroy'));

    // Resolve another request in this application. The prior guard's URL must not leak into it.
    $this->actingAs($primary, 'web')->put(route($primaryName . 'update'), ['name' => $primary->name, 'email' => 'primary-new@example.test'])->assertRedirect()->assertSessionHasNoErrors();
    expect(Notification::sent(new AnonymousNotifiable, VerifyEmailChange::class)->last()->url)
        ->toStartWith(route('laranail-auth.user-email-change.verify') . '?token=')
        ->and(config('laranail.authkit.email_change.verification_url'))->toBeNull();

    $this->actingAs($admin, 'admin');
    $form = $this->presetForm($this->get($adminUrl)->assertOk()->getContent(), 'token');
    expect($form['action'])->toBe(route('laranail-auth.admin.user-email-change.confirm'));
    $this->{$form['method']}($form['action'], $form['fields'])->assertRedirect(route($adminName . 'edit'))->assertSessionHasNoErrors();
    expect($admin->fresh()->email)->toBe('admin-new@example.test')
        ->and($primary->fresh()->email)->toBe($primary->email);
});
