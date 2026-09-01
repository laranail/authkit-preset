<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AuthKit\Preset\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Simtabi\Laranail\AuthKit\Http\Controllers\AbstractEmailVerificationPromptController;
use Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset;

class EmailVerificationPromptController extends AbstractEmailVerificationPromptController
{
    protected function prompt(Request $request): mixed
    {
        return view(AuthPreset::view('verify-email'));
    }
}
