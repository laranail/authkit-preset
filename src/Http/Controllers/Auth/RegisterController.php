<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AuthKit\Preset\Http\Controllers\Auth;

use Illuminate\View\View;
use Simtabi\Laranail\AuthKit\Http\Controllers\AbstractRegisterController;
use Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset;

class RegisterController extends AbstractRegisterController
{
    public function create(): View
    {
        return view(view: AuthPreset::view(page: 'register'));
    }
}
