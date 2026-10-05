<?php

declare(strict_types=1);

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;
use Simtabi\Laranail\AuthKit\Preset\View\Components\DeprecatedSocialButtons;

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

// Blade::render() keeps the compiled template keyed by its source, so without deleteCachedView a
// tag that stopped resolving would still render from an earlier run's compiled copy.
it(description: 'renders the social package buttons through the scoped tag', closure: function (): void {
    registerSocialLoginStubViews();

    expect(Blade::render('<x-laranail-authkit-preset::social-buttons />', deleteCachedView: true))
        ->toContain('data-social-buttons');
});

it(description: 'keeps the deprecated bare tag rendering, and announces it once', closure: function (): void {
    registerSocialLoginStubViews();
    DeprecatedSocialButtons::forgetWarnings();

    $raised = [];
    set_error_handler(function (int $errno, string $message) use (&$raised): bool {
        $raised[] = $message;

        return true;
    }, E_USER_DEPRECATED);

    try {
        $first = Blade::render('<x-authkit-social-buttons />', deleteCachedView: true);
        $second = Blade::render('<x-authkit-social-buttons />', deleteCachedView: true);
    } finally {
        restore_error_handler();
    }

    expect($first)->toContain('data-social-buttons')
        ->and($second)->toContain('data-social-buttons')
        ->and($raised)->toHaveCount(1)
        ->and($raised[0])->toContain('<x-laranail-authkit-preset::social-buttons />');
});

it(description: 'renders nothing through either tag without the social package', closure: function (): void {
    expect(trim(Blade::render('<x-laranail-authkit-preset::social-buttons />', deleteCachedView: true)))->toBe('')
        ->and(trim(Blade::render('<x-authkit-social-buttons />', deleteCachedView: true)))->toBe('');
});
