<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AuthKit\Preset\Http\Controllers\Auth;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Simtabi\Laranail\AuthKit\Services\EmailChange;
use Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset;
use Laravel\Fortify\Http\Controllers\ProfileInformationController;

class UpdateProfileInformationController extends ProfileInformationController
{
    public function create(Request $request, EmailChange $changes): View
    {
        return view(AuthPreset::view('update-profile-information'), [
            'request'            => $request,
            'user'               => $request->user(AuthPreset::guardForCurrentRoute()),
            'pendingEmailChange' => $changes->pending($request->user(AuthPreset::guardForCurrentRoute())),
        ]);
    }
}
