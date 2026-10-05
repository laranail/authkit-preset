<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AuthKit\Preset\Http\Controllers\Auth;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Simtabi\Laranail\AuthKit\Support\AuthResult;
use Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset;
use Simtabi\Laranail\AuthKit\Contracts\LoginUserInterface;
use Simtabi\Laranail\AuthKit\Services\TwoFactorAuthentication;
use Simtabi\Laranail\AuthKit\Http\Controllers\AbstractAttemptEmailPasswordLoginController;

class LoginController extends AbstractAttemptEmailPasswordLoginController
{
    public function create(): View
    {
        return view(view: AuthPreset::view(page: 'login'));
    }

    protected function guard(): string
    {
        return AuthPreset::guardForCurrentRoute();
    }

    protected function handlePassed(Request $request, AuthResult $result, LoginUserInterface $loginAction): mixed
    {
        if (app(TwoFactorAuthentication::class)->enabled($result->user)) {
            $request->session()->put('authkit.two_factor_pending', [
                'user_id'  => $result->user->getAuthIdentifier(),
                'guard'    => $this->guard(),
                'remember' => $request->boolean('remember'),
            ]);
            $request->session()->regenerate();

            return redirect()->route(AuthPreset::routeName('two-factor.challenge'));
        }

        return parent::handlePassed($request, $result, $loginAction);
    }
}
