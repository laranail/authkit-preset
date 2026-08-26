<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AuthKitPreset\Http\Controllers\Auth;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Simtabi\Laranail\AuthKitPreset\Support\AuthPreset;

class PasskeysController
{
    public function index(Request $request): View
    {
        return view(AuthPreset::view('passkeys'), [
            'passkeys' => $request->user()->passkeys,
        ]);
    }
}
