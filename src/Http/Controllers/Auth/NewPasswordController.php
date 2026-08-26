<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AuthKitPreset\Http\Controllers\Auth;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Simtabi\Laranail\AuthKitPreset\Support\AuthPreset;
use Simtabi\Laranail\AuthKit\Http\Controllers\AbstractNewPasswordController;

class NewPasswordController extends AbstractNewPasswordController
{
    public function create(Request $request): View
    {
        return view(AuthPreset::view('reset-password'), [
            'request' => $request,
        ]);
    }
}
