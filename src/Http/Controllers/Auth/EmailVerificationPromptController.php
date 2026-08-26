<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AuthKitPreset\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Simtabi\Laranail\AuthKitPreset\Support\AuthPreset;
use Simtabi\Laranail\AuthKit\Http\Controllers\AbstractEmailVerificationPromptController;

class EmailVerificationPromptController extends AbstractEmailVerificationPromptController
{
    protected function prompt(Request $request): mixed
    {
        return view(AuthPreset::view('verify-email'));
    }
}
