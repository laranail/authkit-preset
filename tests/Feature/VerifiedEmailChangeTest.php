<?php

declare(strict_types=1);

use Workbench\App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\AnonymousNotifiable;
use Simtabi\Laranail\AuthKit\Services\EmailChange;
use Simtabi\Laranail\AuthKit\Notifications\VerifyEmailChange;

uses(Tests\Concerns\UsesPublishedPresetViews::class);

beforeEach(function (): void {
    $this->publishPresetViews();
    Notification::fake();
    $this->user = User::factory()->create(['email' => 'original@example.test']);
});
afterEach(fn () => $this->removePublishedPresetViews());

function presetEmailNotification(): VerifyEmailChange
{
    return Notification::sent(new AnonymousNotifiable, VerifyEmailChange::class)->last();
}

function presetEmailProof(): string
{
    parse_str(parse_url(presetEmailNotification()->url, PHP_URL_QUERY), $query);

    return $query['token'];
}

it('completes a verified change through the published profile and confirmation forms', function (): void {
    $form = $this->presetForm($this->actingAs($this->user)->get(route('user-profile-information.edit'))->assertOk()->getContent(), 'email');
    expect($form['method'])->toBe('put');
    $this->{$form['method']}($form['action'], [...$form['fields'], 'name' => 'Updated', 'email' => 'NEW@example.test'])
        ->assertRedirect()->assertSessionHasNoErrors();
    expect($this->user->fresh()->email)->toBe('original@example.test')->and($this->user->fresh()->name)->toBe('Updated');
    Notification::assertSentOnDemand(VerifyEmailChange::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'new@example.test');
    $this->get(route('user-profile-information.edit'))->assertOk()->assertSee('new@example.test')->assertSee('Resend verification link')->assertSee('Cancel email change');

    $url = presetEmailNotification()->url;
    expect($url)->toStartWith(route('laranail-auth.user-email-change.verify') . '?token=');
    $confirmation = $this->get($url)->assertOk()->assertSee('new@example.test')->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private')->assertHeader('Referrer-Policy', 'no-referrer');
    expect($this->user->fresh()->email)->toBe('original@example.test');
    $form = $this->presetForm($confirmation->getContent(), 'token');
    $this->{$form['method']}($form['action'], $form['fields'])->assertRedirect(route('user-profile-information.edit'))
        ->assertSessionHas('status', 'email-changed')->assertSessionHasNoErrors();
    expect($this->user->fresh()->email)->toBe('new@example.test')->and($this->user->fresh()->email_verified_at)->not->toBeNull();
    $this->get(route('user-profile-information.edit'))->assertSee('Your email address has been changed and verified.')->assertDontSee('Resend verification link');
    $this->post($form['action'], $form['fields'])->assertSessionHasErrorsIn('emailChange', ['email']);
});

it('resends with a fresh proof and cancels without changing the active email', function (): void {
    app(EmailChange::class)->request($this->user, 'new@example.test');
    $oldProof = presetEmailProof();
    $this->actingAs($this->user)->post(route('laranail-auth.user-email-change.resend'))->assertRedirect(route('user-profile-information.edit'))->assertSessionHas('status', 'email-change-sent');
    $newProof = presetEmailProof();
    expect($newProof)->not->toBe($oldProof);
    $this->post(route('laranail-auth.user-email-change.confirm'), ['token' => $oldProof])->assertSessionHasErrorsIn('emailChange', ['email']);
    $this->delete(route('laranail-auth.user-email-change.destroy'))->assertRedirect(route('user-profile-information.edit'))->assertSessionHas('status', 'email-change-cancelled');
    $this->post(route('laranail-auth.user-email-change.confirm'), ['token' => $newProof])->assertSessionHasErrorsIn('emailChange', ['email']);
    expect($this->user->fresh()->email)->toBe('original@example.test')->and(app(EmailChange::class)->pending($this->user))->toBeNull();
});

it('shows useful errors for expired invalid and foreign-account links without flashing proofs', function (string $reason): void {
    app(EmailChange::class)->request($this->user, 'new@example.test');
    $proof = presetEmailProof();
    $user = $this->user;
    if ($reason === 'expired') {
        $this->travel(61)->minutes();
    } elseif ($reason === 'invalid') {
        $proof = bin2hex(random_bytes(32));
    } elseif ($reason === 'malformed') {
        $proof = 'invalid';
    } else {
        $user = User::factory()->create();
    }
    $before = $user->email;
    $this->actingAs($user)->post(route('laranail-auth.user-email-change.confirm'), ['token' => $proof])
        ->assertRedirect(route('user-profile-information.edit'))->assertSessionHasErrorsIn('emailChange')->assertSessionMissing('_old_input.token');
    expect($user->fresh()->email)->toBe($before);
    if ($reason === 'expired') {
        $this->get(route('user-profile-information.edit'))->assertDontSee('Resend verification link');
    }
})->with(['expired', 'invalid', 'malformed', 'foreign']);

it('rejects duplicate targets at request and confirmation and renders the error inline', function (bool $afterRequest): void {
    if ($afterRequest) {
        app(EmailChange::class)->request($this->user, 'taken@example.test');
    }
    User::factory()->create(['email' => 'taken@example.test']);
    $this->actingAs($this->user);
    if ($afterRequest) {
        $this->post(route('laranail-auth.user-email-change.confirm'), ['token' => presetEmailProof()])->assertSessionHasErrorsIn('emailChange', ['email']);
    } else {
        $this->from(route('user-profile-information.edit'))->put(route('user-profile-information.update'), ['name' => 'Updated', 'email' => 'taken@example.test'])->assertSessionHasErrorsIn('updateProfileInformation', ['email']);
    }
    $this->get(route('user-profile-information.edit'))->assertOk()->assertSee('The email has already been taken.');
    expect($this->user->fresh()->email)->toBe('original@example.test');
})->with([false, true]);

it('requires authentication on every email-change browser endpoint', function (string $method, string $name): void {
    $this->{$method}(route('laranail-auth.user-email-change.' . $name))->assertRedirect();
    expect(app(EmailChange::class)->pending($this->user))->toBeNull();
})->with([['get', 'verify'], ['post', 'confirm'], ['post', 'resend'], ['delete', 'destroy']]);

it('does not offer confirmation when the proof or pending change is missing', function (): void {
    $this->actingAs($this->user)->get(route('laranail-auth.user-email-change.verify'))->assertOk()
        ->assertSee('This email change is unavailable or expired.')->assertDontSee('name="token"', false);
});

it('preserves an explicitly configured client verification URL', function (): void {
    config(['laranail.authkit.email_change.verification_url' => 'https://client.example.test/change?token={token}']);
    $this->actingAs($this->user)->put(route('user-profile-information.update'), ['name' => 'Updated', 'email' => 'new@example.test'])->assertRedirect()->assertSessionHasNoErrors();
    expect(presetEmailNotification()->url)->toStartWith('https://client.example.test/change?token=')
        ->and(config('laranail.authkit.email_change.verification_url'))->toBe('https://client.example.test/change?token={token}');
});

it('throttles profile changes and mail resends together', function (): void {
    app(EmailChange::class)->request($this->user, 'new@example.test');
    $this->actingAs($this->user);
    for ($i = 0; $i < 6; $i++) {
        $this->post(route('laranail-auth.user-email-change.resend'))->assertRedirect()->assertSessionHasNoErrors();
    }
    $this->post(route('laranail-auth.user-email-change.resend'))->assertStatus(429);
    $this->put(route('user-profile-information.update'), ['name' => 'Updated', 'email' => 'new@example.test'])->assertStatus(429);
    Notification::assertSentOnDemandTimes(VerifyEmailChange::class, 7);
});

it('requires a valid csrf token before applying an email change', function (): void {
    app(EmailChange::class)->request($this->user, 'new@example.test');
    $proof = presetEmailProof();
    $csrf = bin2hex(random_bytes(20));
    $environment = app('env');
    $this->actingAs($this->user)->withSession(['_token' => $csrf]);
    app()->instance('env', 'production');
    try {
        $this->post(route('laranail-auth.user-email-change.confirm'), ['token' => $proof])->assertStatus(419);
        expect($this->user->fresh()->email)->toBe('original@example.test');
        $this->post(route('laranail-auth.user-email-change.confirm'), ['token' => $proof, '_token' => $csrf])
            ->assertRedirect(route('user-profile-information.edit'))->assertSessionHasNoErrors();
        expect($this->user->fresh()->email)->toBe('new@example.test');
    } finally {
        app()->instance('env', $environment);
    }
});

it('returns a signed-out user to email confirmation after signing in through the published form', function (): void {
    $password = Simtabi\Laranail\Package\Tools\Testing\TestCredentials::password('confirmation');
    $this->user->forceFill(['password' => $password])->save();
    app(EmailChange::class)->request($this->user, 'new@example.test');
    $url = presetEmailNotification()->url;
    $this->get($url)->assertRedirect(route('login'))->assertSessionHas('url.intended', $url);
    $form = $this->presetForm($this->get(route('login'))->assertOk()->getContent(), 'password');
    $this->{$form['method']}($form['action'], [...$form['fields'], 'email' => $this->user->email, 'password' => $password])
        ->assertRedirect($url)->assertSessionHasNoErrors();
    $this->get($url)->assertOk()->assertSee('new@example.test')->assertSee('name="token"', false);
    expect($this->user->fresh()->email)->toBe('original@example.test');
});
