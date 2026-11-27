<?php

declare(strict_types=1);

use Workbench\App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Simtabi\Laranail\Package\Tools\Testing\TestCredentials;

uses(Tests\Concerns\UsesPublishedPresetViews::class);
beforeEach(fn () => $this->publishPresetViews());
afterEach(fn () => $this->removePublishedPresetViews());

it('registers through the published form with backend field names and confirmation', function (): void {
    Notification::fake();
    $form = $this->presetForm($this->get(route('register'))->assertOk()->getContent(), 'password_confirmation');
    $password = TestCredentials::password('registration');
    $this->{$form['method']}($form['action'], [...$form['fields'], 'name' => 'Published User', 'email' => 'published@example.test', 'password' => $password, 'password_confirmation' => $password])->assertRedirect()->assertSessionHasNoErrors();
    $this->assertAuthenticated();
    expect(auth()->user()->email)->toBe('published@example.test')->and(Hash::check($password, auth()->user()->password))->toBeTrue();
});

it('requests and completes a reset through the published forms with the real password broker', function (): void {
    Notification::fake();
    $user = User::factory()->create();
    $form = $this->presetForm($this->get(route('password.request'))->assertOk()->getContent(), 'email');
    $this->{$form['method']}($form['action'], [...$form['fields'], 'email' => $user->email])->assertRedirect()->assertSessionHasNoErrors();
    Notification::assertSentTo($user, ResetPassword::class);
    $notification = Notification::sent($user, ResetPassword::class)->first();
    $url = route('password.reset', ['token' => $notification->token, 'email' => $user->email]);
    $form = $this->presetForm($this->get($url)->assertOk()->getContent(), 'password_confirmation');
    $password = TestCredentials::password('reset');
    $this->{$form['method']}($form['action'], [...$form['fields'], 'password' => $password, 'password_confirmation' => $password])->assertRedirect()->assertSessionHasNoErrors();
    expect(Hash::check($password, $user->fresh()->password))->toBeTrue();
});

it('uses the real configured password rules and renders their errors in the published form', function (): void {
    Illuminate\Validation\Rules\Password::defaults(fn () => Illuminate\Validation\Rules\Password::min(24));
    try {
        $form = $this->presetForm($this->get(route('register'))->assertOk()->getContent(), 'password_confirmation');
        $password = TestCredentials::password('short');
        // A generated value below this test's configured policy, without a literal credential.
        $password = substr($password, 0, 12);
        $this->from(route('register'))->{$form['method']}($form['action'], [...$form['fields'], 'name' => 'Published User', 'email' => 'published@example.test', 'password' => $password, 'password_confirmation' => $password])->assertSessionHasErrors('password');
        $this->get(route('register'))->assertSee('24 characters')->assertDontSee('At least 8 characters');
        $this->assertGuest();
    } finally {
        Illuminate\Validation\Rules\Password::defaults(fn () => Illuminate\Validation\Rules\Password::min(8));
    }
});
