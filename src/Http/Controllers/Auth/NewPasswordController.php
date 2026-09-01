<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AuthKit\Preset\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Simtabi\Laranail\AuthKit\Http\Controllers\AbstractNewPasswordController;
use Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset;

class NewPasswordController extends AbstractNewPasswordController
{
    public function create(Request $request): View
    {
        return view(AuthPreset::view('reset-password'), [
            'request' => $request,
        ]);
    }
}
