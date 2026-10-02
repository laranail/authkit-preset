<?php

declare(strict_types=1);

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;

/*
 * The social package is a `suggest`, not a `require`, so CI installs it not at all. These
 * tests pin the two directions of that contract. The preset-owned component gives the optional
 * package's component a stable call site without requiring the package in the preset itself.
 */
function registerSocialLoginStubViews(): void
{
    $path = dirname(__DIR__) . '/fixtures/social-login-views';
    View::addNamespace('laranail/authkit-social-login', [$path]);
    Blade::anonymousComponentPath($path . '/components', 'laranail-authkit-social-login');
}

it(description: 'renders login without the social package installed', closure: function (): void {
    expect(view()->exists('laranail/authkit-social-login::components.social-buttons'))->toBeFalse();

    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('data-social-buttons', escape: false);
});

it(description: 'renders registration without the social package installed', closure: function (): void {
    expect(view()->exists('laranail/authkit-social-login::components.social-buttons'))->toBeFalse();

    $this->get(route('register'))
        ->assertOk()
        ->assertDontSee('data-social-buttons', escape: false);
});

it(description: 'renders the social package buttons on login when it is installed', closure: function (): void {
    registerSocialLoginStubViews();

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('data-social-buttons', escape: false);
});

it(description: 'renders the social package buttons on registration when it is installed', closure: function (): void {
    registerSocialLoginStubViews();

    $this->get(route('register'))
        ->assertOk()
        ->assertSee('data-social-buttons', escape: false);
});
