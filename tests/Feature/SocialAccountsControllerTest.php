<?php

declare(strict_types=1);

use Workbench\App\Models\User;
use Simtabi\Laranail\AuthKit\Social\Models\Social;
use Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset;

function linkSocial(User $user, string $provider, string $id): Social
{
    return Social::query()->create([
        'socialable_type' => $user::class, 'socialable_id' => $user->getKey(),
        'provider' => $provider, 'provider_id' => $id, 'email' => $user->email,
    ]);
}

it('lists a user’s connected accounts', function (): void {
    $user = User::factory()->create();
    linkSocial($user, 'google', 'g-1');

    $this->actingAs($user)->get('/auth/user/social-accounts')
        ->assertOk()
        ->assertSee('Google');
});

it('disconnects a provider when another remains', function (): void {
    $user = User::factory()->create();
    linkSocial($user, 'google', 'g-1');
    linkSocial($user, 'x', 'x-1');

    $this->actingAs($user)
        ->delete(AuthPreset::webPrefix() . '/user/social-accounts/google')
        ->assertSessionHas('status', 'social-account-unlinked');

    expect(Social::query()->count())->toBe(1);
});

it('refuses to disconnect the only sign-in method, and says so', function (): void {
    $user = User::factory()->create();
    linkSocial($user, 'google', 'g-1');

    $this->actingAs($user)
        ->delete(AuthPreset::webPrefix() . '/user/social-accounts/google')
        ->assertSessionHasErrors('provider');

    expect(Social::query()->count())->toBe(1);
});

it('shows the control as unavailable rather than offering it', function (): void {
    // Removing the only way into an account is a thing to prevent, not to report after the fact.
    $user = User::factory()->create();
    linkSocial($user, 'google', 'g-1');

    $this->actingAs($user)->get('/auth/user/social-accounts')
        ->assertOk()
        ->assertSee('Only sign-in method')
        ->assertDontSee('Disconnect');
});

it('404s an unknown provider', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(AuthPreset::webPrefix() . '/user/social-accounts/myspace')
        ->assertNotFound();
});

it('requires authentication', function (): void {
    $this->get('/auth/user/social-accounts')->assertRedirect();
});
