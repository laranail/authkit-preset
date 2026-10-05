<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AuthKit\Preset\Http\Controllers\Auth;

use BaconQrCode\Writer;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use BaconQrCode\Renderer\ImageRenderer;
use Illuminate\Validation\ValidationException;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset;
use Simtabi\Laranail\AuthKit\Services\TwoFactorAuthentication;

final class TwoFactorController
{
    public function index(Request $request, TwoFactorAuthentication $twoFactor): View
    {
        $user = $request->user(AuthPreset::guardForCurrentRoute());

        return view(AuthPreset::view('two-factor-settings'), [
            'enabled'           => $twoFactor->enabled($user),
            'recoveryCodeCount' => count($twoFactor->recoveryCodes($user)),
        ]);
    }

    public function begin(Request $request, TwoFactorAuthentication $twoFactor): View
    {
        $user = $request->user(AuthPreset::guardForCurrentRoute());

        if ($twoFactor->enabled($user)) {
            return redirect()->route(AuthPreset::routeName('user-two-factor.index'));
        }

        $setup = $twoFactor->begin($user);

        return view(AuthPreset::view('two-factor-setup'), [
            ...$setup,
            'qrCodeSvg' => $this->qrCodeSvg($setup['qr_code_url']),
        ]);
    }

    public function confirm(Request $request, TwoFactorAuthentication $twoFactor): View|RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/']]);
        $codes = $twoFactor->confirm($request->user(AuthPreset::guardForCurrentRoute()), $request->string('code')->toString());

        if ($codes === null) {
            throw ValidationException::withMessages(['code' => 'The authenticator code is invalid.']);
        }

        return view(AuthPreset::view('two-factor-recovery-codes'), ['recoveryCodes' => $codes]);
    }

    public function disable(Request $request, TwoFactorAuthentication $twoFactor): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'min:6', 'max:20']]);
        $user = $request->user(AuthPreset::guardForCurrentRoute());

        if (! $twoFactor->verify($user, $request->string('code')->toString())) {
            throw ValidationException::withMessages(['code' => 'The authenticator or recovery code is invalid.']);
        }

        $twoFactor->disable($user);

        return redirect()->route(AuthPreset::routeName('user-two-factor.index'))->with('status', 'two-factor-disabled');
    }

    public function regenerateRecoveryCodes(Request $request, TwoFactorAuthentication $twoFactor): View|RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'min:6', 'max:20']]);
        $user = $request->user(AuthPreset::guardForCurrentRoute());

        if (! $twoFactor->verify($user, $request->string('code')->toString())) {
            throw ValidationException::withMessages(['code' => 'The authenticator or recovery code is invalid.']);
        }

        return view(AuthPreset::view('two-factor-recovery-codes'), [
            'recoveryCodes' => $twoFactor->rotateRecoveryCodes($user),
        ]);
    }

    public function challenge(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get('authkit.two_factor_pending');

        if (! is_array($pending)) {
            return redirect()->route(AuthPreset::routeName('login'));
        }

        return view(AuthPreset::view('two-factor-challenge'));
    }

    public function verifyChallenge(Request $request, TwoFactorAuthentication $twoFactor): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'min:6', 'max:20']]);
        $pending = $request->session()->get('authkit.two_factor_pending');

        if (! is_array($pending) || ! isset($pending['guard'], $pending['user_id'])) {
            return redirect()->route(AuthPreset::routeName('login'));
        }

        $guard = Auth::guard($pending['guard']);
        $user = $guard->getProvider()->retrieveById($pending['user_id']);

        if ($user === null || ! $twoFactor->verify($user, $request->string('code')->toString())) {
            throw ValidationException::withMessages(['code' => 'The authenticator or recovery code is invalid.']);
        }

        $guard->login($user, (bool) ($pending['remember'] ?? false));
        $request->session()->forget('authkit.two_factor_pending');
        $request->session()->regenerate();
        $request->session()->put('authkit.two_factor_verified', true);

        return redirect()->intended(AuthPreset::afterLoginRedirect());
    }

    private function qrCodeSvg(string $url): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(192),
            new SvgImageBackEnd,
        );

        return (new Writer($renderer))->writeString($url);
    }
}
