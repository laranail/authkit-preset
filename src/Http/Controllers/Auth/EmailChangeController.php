<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AuthKit\Preset\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Simtabi\Laranail\AuthKit\Services\EmailChange;
use Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset;

final class EmailChangeController
{
    public function show(Request $request, EmailChange $changes): Response
    {
        // A GET only displays confirmation. Mail scanners cannot consume the proof.
        $token = $request->query('token');
        $pending = $changes->pending($request->user(AuthPreset::guardForCurrentRoute()));

        return response()->view(AuthPreset::view('verify-email-change'), [
            'pending' => $pending,
            'token'   => is_string($token) && strlen($token) === 64 ? $token : null,
        ])->header('Referrer-Policy', 'no-referrer');
    }

    public function confirm(Request $request, EmailChange $changes): RedirectResponse
    {
        try {
            $input = $request->validate(['token' => ['required', 'string', 'size:64']]);
            $changes->confirm($request->user(AuthPreset::guardForCurrentRoute()), $input['token']);
        } catch (ValidationException $exception) {
            // Do not flash the proof or redirect to a confirmation URL that requires query input.
            return redirect()->route(AuthPreset::routeNameForCurrentRoute('user-profile-information.edit'))
                ->withErrors($exception->validator, 'emailChange');
        }

        return redirect()->route(AuthPreset::routeNameForCurrentRoute('user-profile-information.edit'))
            ->with('status', 'email-changed');
    }

    public function resend(Request $request, EmailChange $changes): RedirectResponse
    {
        $user = $request->user(AuthPreset::guardForCurrentRoute());
        $pending = $changes->pending($user);
        if ($pending === null) {
            return redirect()->route(AuthPreset::routeNameForCurrentRoute('user-profile-information.edit'))
                ->withErrors(['email' => __('laranail/authkit::messages.email_change_invalid')], 'emailChange');
        }
        try {
            $changes->request($user, $pending['email']);
        } catch (ValidationException $exception) {
            return redirect()->route(AuthPreset::routeNameForCurrentRoute('user-profile-information.edit'))
                ->withErrors($exception->validator, 'emailChange');
        }

        return redirect()->route(AuthPreset::routeNameForCurrentRoute('user-profile-information.edit'))
            ->with('status', 'email-change-sent');
    }

    public function destroy(Request $request, EmailChange $changes): RedirectResponse
    {
        $changes->cancel($request->user(AuthPreset::guardForCurrentRoute()));

        return redirect()->route(AuthPreset::routeNameForCurrentRoute('user-profile-information.edit'))
            ->with('status', 'email-change-cancelled');
    }
}
