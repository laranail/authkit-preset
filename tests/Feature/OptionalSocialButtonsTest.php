<?php

declare(strict_types=1);

use Illuminate\Support\Facades\View;

/*
 * The social package is a `suggest`, not a `require`, so CI installs it not at all. These
 * tests pin the two directions of that contract, and the reason it is not written as an
 * <x-laranail-authkit-social-login::social-buttons /> tag inside the same @if: Blade resolves
 * component tags while compiling the template, before any conditional runs, so naming a
 * component the package never registered throws during compilation and takes the whole page
 * down rather than skipping the buttons. @include resolves the view at render time, which is
 * the first moment the guard can mean anything.
 */
function registerSocialLoginStubViews(): void
{
    View::addNamespace('laranail/authkit-social-login', [
        dirname(__DIR__) . '/fixtures/social-login-views',
    ]);
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
