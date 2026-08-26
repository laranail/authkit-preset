<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AuthKitPreset\Http\Controllers\Auth;

use Illuminate\View\View;
use Simtabi\Laranail\AuthKitPreset\Support\AuthPreset;
use Simtabi\Laranail\AuthKit\Http\Controllers\AbstractRegisterController;

class RegisterController extends AbstractRegisterController
{
    public function create(): View
    {
        return view(view: AuthPreset::view(page: 'register'));
    }
}
