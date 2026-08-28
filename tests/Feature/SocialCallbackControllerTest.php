<?php

declare(strict_types=1);

use Workbench\App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Simtabi\Laranail\AuthKit\Social\Models\Social;
use Simtabi\Laranail\AuthKit\Social\Enums\SocialProvider;

beforeEach(closure: function (): void {
    $this->socialiteUser = new SocialiteUser;
    $this->socialiteUser->map(attributes: [
        'id'             => '112837291294199545470',
        'name'           => 'Amos Njogu',
        'nickname'       => 'amosnjogu',
        'email'          => 'amos@example.com',
        'avatar'         => 'https://example.com/avatar.jpg',
        'email_verified' => true,
    ]);
    $this->socialiteUser->setRaw(user: [
        'email_verified' => true,
    ]);
    $this->socialiteUser->token = 'mock-token';
    $this->socialiteUser->refreshToken = 'mock-refresh-token';
    $this->socialiteUser->expiresIn = 3600;
});

it(description: 'redirects to dashboard on successful social login', closure: function (): void {
    Socialite::fake(driver: SocialProvider::GOOGLE->value, user: $this->socialiteUser);

    $response = $this->get(uri: route(name: 'social.callback', parameters: ['provider' => 'google']));

    $response->assertRedirect();
    expect(value: auth()->check())->toBeTrue()
        ->and(value: auth()->user()->email)->toBe(expected: 'amos@example.com');
});

it(description: 'returns existing user when social account already exists', closure: function (): void {
    Socialite::fake(driver: SocialProvider::GOOGLE->value, user: $this->socialiteUser);

    $existingUser = User::factory()->create(attributes: ['email' => 'amos@example.com']);
    Social::create([
        'socialable_type' => User::class,
        'socialable_id'   => $existingUser->id,
        'provider'        => 'google',
        'provider_id'     => '112837291294199545470',
        'name'            => 'Amos Njogu',
        'email'           => 'amos@example.com',
        'token'           => 'old-token',
        'refresh_token'   => 'old-refresh-token',
    ]);

    $response = $this->get(uri: route(name: 'social.callback', parameters: ['provider' => 'google']));

    $response->assertRedirect();
    expect(value: auth()->id())->toBe(expected: $existingUser->id);
});

it(description: 'redirects to login on failed social login', closure: function (): void {
    $noEmailUser = new SocialiteUser;
    $noEmailUser->map(attributes: [
        'id'       => '112837291294199545470',
        'name'     => 'No Email',
        'nickname' => 'noemail',
    ]);
    $noEmailUser->token = 'mock-token';
    $noEmailUser->refreshToken = 'mock-refresh';

    Socialite::fake(driver: SocialProvider::GOOGLE->value, user: $noEmailUser);

    $response = $this->get(uri: route(name: 'social.callback', parameters: ['provider' => 'google']));

    $response->assertRedirect(uri: route(name: 'login'));
});

it(description: 'does not auto-link by email when provider has not verified it (B1 regression)', closure: function (): void {
    $existingUser = User::factory()->create(attributes: ['email' => 'amos@example.com']);

    $unverifiedUser = new SocialiteUser;
    $raw = [
        'id'             => '112837291294199545470',
        'name'           => 'Attacker',
        'nickname'       => 'attacker',
        'email'          => 'amos@example.com',
        'avatar'         => 'https://example.com/avatar.jpg',
        'email_verified' => false,
    ];
    $unverifiedUser->setRaw(user: $raw);
    $unverifiedUser->map(attributes: $raw);
    $unverifiedUser->token = 'mock-token';
    $unverifiedUser->refreshToken = 'mock-refresh';
    $unverifiedUser->expiresIn = 3600;

    Socialite::fake(driver: SocialProvider::GOOGLE->value, user: $unverifiedUser);

    $response = $this->get(uri: route(name: 'social.callback', parameters: ['provider' => 'google']));

    $response->assertRedirect(uri: route(name: 'login'));
    expect(value: auth()->check())->toBeFalse()
        ->and(value: auth()->id())->not->toBe(expected: $existingUser->id);
});

it(description: 'accepts the POST callback Apple sends, and exempts it from CSRF', closure: function (): void {
    // Apple requests the name and email scopes, which forces response_mode=form_post, so it
    // POSTs this endpoint from its own servers with no session and no CSRF token. A GET-only
    // route answers 405 and a CSRF-protected one answers 419; either kills Apple sign-in with
    // nothing in the log to explain it.
    Socialite::fake(driver: SocialProvider::APPLE->value, user: $this->socialiteUser);

    // The verb, asserted through a real request.
    expect(value: $this->post(uri: '/auth/social/apple/callback')->getStatusCode())->not->toBe(405);

    // The CSRF exemption, asserted against the registered route rather than a response, because
    // PreventRequestForgery skips validation outright while running unit tests -- a 419 can never
    // be observed here, so asserting on the status code would pin nothing.
    //
    // PreventRequestForgery is what the `web` group actually registers; VerifyCsrfToken and
    // ValidateCsrfToken are deprecated subclasses of it, and excluding either would silently
    // do nothing.
    $route = app('router')->getRoutes()->getByName('social.callback');

    expect(value: $route)->not->toBeNull()
        ->and($route->methods())->toContain('POST')
        ->and($route->excludedMiddleware())
        ->toContain(Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
});
