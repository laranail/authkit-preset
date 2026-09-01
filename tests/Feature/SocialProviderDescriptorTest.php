<?php

declare(strict_types=1);

use Simtabi\Laranail\AuthKit\Contracts\IdentityProviderRegistryInterface;
use Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset;
use Simtabi\Laranail\AuthKit\Support\IdentityProvider;

beforeEach(function (): void {
    config()->set('laranail.authkit-social.google.client_id', 'google-id');
    config()->set('laranail.authkit-social.x.client_id', 'x-id');
});

it('describes a built-in provider with its own label and a conventional icon', function (): void {
    config()->set('laranail.authkit-preset.social.providers', ['google']);

    expect(AuthPreset::socialProviders())->toBe([[
        'slug' => 'google',
        'label' => 'Google',
        'icon' => 'laranail/authkit-preset::icons.google',
        'class' => '',
        'order' => 0,
    ]]);
});

it('renders a provider a sub-package registered', function (): void {
    // The previous implementation validated the slug against the SocialProvider enum, so a
    // registered provider was filtered out here and could never appear however correctly it had
    // been registered -- the registry seam stopped one step short of a working button.
    app(IdentityProviderRegistryInterface::class)->register(new IdentityProvider(
        slug: 'okta', label: 'Okta', assertsEmailVerified: true,
    ));
    config()->set('laranail.authkit-social.okta.client_id', 'okta-id');
    config()->set('laranail.authkit-preset.social.providers', ['okta']);

    expect(AuthPreset::socialProviders())->toHaveCount(1)
        ->and(AuthPreset::socialProviders()[0]['slug'])->toBe('okta')
        ->and(AuthPreset::socialProviders()[0]['label'])->toBe('Okta');
});

it('lets configuration override the label, icon, classes and order', function (): void {
    config()->set('laranail.authkit-preset.social.providers', ['google', 'x']);
    config()->set('laranail.authkit-preset.social.ui', [
        'google' => ['label' => 'Sign in with Google', 'class' => 'ring-2', 'order' => 20],
        'x' => ['icon' => 'icons.custom-x', 'order' => 10],
    ]);

    $providers = AuthPreset::socialProviders();

    // x first: ordering is configuration, not the order of the providers array.
    expect(array_column($providers, 'slug'))->toBe(['x', 'google'])
        ->and($providers[0]['icon'])->toBe('icons.custom-x')
        ->and($providers[1]['label'])->toBe('Sign in with Google')
        ->and($providers[1]['class'])->toBe('ring-2');
});

it('drops a configured provider with no credentials', function (): void {
    // A button that fails at the provider is worse than no button.
    config()->set('laranail.authkit-preset.social.providers', ['google', 'linkedin']);
    config()->set('laranail.authkit-social.linkedin.client_id', null);

    expect(array_column(AuthPreset::socialProviders(), 'slug'))->toBe(['google']);
});

it('drops a slug that is neither a built-in nor registered', function (): void {
    config()->set('laranail.authkit-preset.social.providers', ['google', 'myspace']);
    config()->set('laranail.authkit-social.myspace.client_id', 'ms-id');

    expect(array_column(AuthPreset::socialProviders(), 'slug'))->toBe(['google']);
});

it('keeps enabledSocialProviders() answering slugs for published views', function (): void {
    config()->set('laranail.authkit-preset.social.providers', ['google', 'x']);

    expect(AuthPreset::enabledSocialProviders())->toBe(['google', 'x']);
});
