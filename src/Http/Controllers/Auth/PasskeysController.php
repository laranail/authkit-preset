<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AuthKit\Preset\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset;

class PasskeysController
{
    public function index(Request $request): View
    {
        return view(AuthPreset::view('passkeys'), [
            'passkeys' => $request->user()->passkeys,
        ]);
    }
}
