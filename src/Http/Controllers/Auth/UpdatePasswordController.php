<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AuthKit\Preset\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Fortify\Http\Controllers\PasswordController;
use Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset;

class UpdatePasswordController extends PasswordController
{
    public function create(Request $request): View
    {
        return view(AuthPreset::view('update-password'), [
            'request' => $request,
        ]);
    }
}
