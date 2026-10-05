<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AuthKit\Preset\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\HtmlString;

/**
 * The bare `<x-authkit-social-buttons />` tag, kept so views published before the rename keep
 * compiling.
 *
 * Renders exactly what {@see OptionalSocialButtons} renders, and announces itself once per process
 * with E_USER_DEPRECATED, so a page that renders it on every request does not write a line per
 * request.
 *
 * @deprecated Since 0.1. Use `<x-laranail-authkit-preset::social-buttons />`. Earliest removal:
 *             the next minor after 0.1.
 */
final class DeprecatedSocialButtons extends Component
{
    private static bool $announced = false;

    /** Forget that the notice was raised. For test suites; a process announces it once by design. */
    public static function forgetWarnings(): void
    {
        self::$announced = false;
    }

    public function render(): HtmlString
    {
        if (! self::$announced) {
            self::$announced = true;

            trigger_error(
                'laranail/authkit-preset: the Blade component <x-authkit-social-buttons /> is deprecated '
                . 'and will stop resolving no earlier than the next minor after 0.1; use '
                . '<x-laranail-authkit-preset::social-buttons />.',
                E_USER_DEPRECATED,
            );
        }

        return (new OptionalSocialButtons)->render();
    }
}
