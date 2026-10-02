<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AuthKit\Preset\View\Components;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;

/** Renders Auth Kit Social Login's component when that optional package is installed. */
final class OptionalSocialButtons extends Component
{
    public function render(): HtmlString
    {
        $view = 'laranail/authkit-social-login::components.social-buttons';

        if (! view()->exists($view)) {
            return new HtmlString('');
        }

        return new HtmlString(Blade::render('<x-laranail-authkit-social-login::social-buttons />'));
    }
}
