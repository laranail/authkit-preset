<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| One mount of the web routes
|--------------------------------------------------------------------------
|
| Required once per configured mount from routes/web.php, which supplies $prefix, $guard and
| wraps this in the route-name prefix. Kept as a single file rather than duplicated per guard so
| a route added for the primary user population cannot be forgotten for the others.
|
| @var string $prefix  URL prefix for this mount, e.g. 'auth' or 'admin/auth'
| @var string $guard   the auth guard these routes protect
|
*/

use Illuminate\Support\Facades\Route;
use Laravel\Passkeys\Http\Controllers\PasskeyLoginController;
use Laravel\Fortify\Http\Controllers\VerifyEmailController as FortifyVerifyEmailController;
use Laravel\Passkeys\Http\Controllers\PasskeyConfirmationController;
use Laravel\Passkeys\Http\Controllers\PasskeyRegistrationController;
use Laravel\Fortify\Http\Controllers\ConfirmablePasswordController;
use Laravel\Fortify\Http\Controllers\ConfirmedPasswordStatusController;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController as FortifyEmailVerificationNotificationController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Simtabi\Laranail\AuthKit\Preset\Features;
use Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset;
use Simtabi\Laranail\AuthKit\Preset\Http\Controllers\Auth;
use Simtabi\Laranail\AuthKit\Preset\Http\Middleware\ValidateCaptcha;

// The landing page sits outside the prefix, at a bare /dashboard, so it can only belong to one
// mount: a second population registering it again would claim the same URI behind its own guard
// and take the page away from the first. Additional populations land on their own prefixed page.
Route::middleware([...AuthPreset::webMiddleware(), 'auth:' . $guard])
    ->get($isPrimaryMount ? '/dashboard' : $prefix . '/dashboard', fn () => view(AuthPreset::view('dashboard'), ['user' => request()->user()]))
    ->name('dashboard');

Route::prefix($prefix)
    ->middleware([...AuthPreset::webMiddleware(), 'guest:' . $guard])
    ->group(function () use ($prefix, $guard, $isPrimaryMount): void {
        if (Features::enabled(Features::registration())) {
            Route::get('/register', [Auth\RegisterController::class, 'create'])->name('register');
            Route::post('/register', [Auth\RegisterController::class, 'store'])
                ->middleware(['throttle:10,1', ValidateCaptcha::class])
                ->name('register.store');
        }

        if (Features::enabled(Features::login())) {
            Route::get('/login', [Auth\LoginController::class, 'create'])->name('login');
            Route::post('/login', [Auth\LoginController::class, 'store'])
                ->middleware(['throttle:10,1', ValidateCaptcha::class])
                ->name('login.store');
        }

        if (Features::enabled(Features::social())) {
            Route::get('/social/{provider}', Auth\SocialRedirectController::class)->name('social.redirect');
            // Apple requests the `name` and `email` scopes, which forces response_mode=form_post,
            // so Apple POSTs this callback rather than redirecting to it. A GET-only route answers
            // Apple with a 405 and the sign-in dies with nothing in the log to explain it, so the
            // callback accepts both verbs.
            //
            // CSRF is excluded because the request originates at Apple and carries no session token
            // by construction. The OAuth `state` parameter Socialite round-trips is what protects
            // this endpoint; CSRF never did. PreventRequestForgery is named directly because it is
            // what the `web` group actually registers -- VerifyCsrfToken and ValidateCsrfToken are
            // deprecated subclasses, and excluding either silently does nothing.
            Route::match(['GET', 'POST'], '/social/{provider}/callback', Auth\SocialCallbackController::class)
                ->withoutMiddleware(PreventRequestForgery::class)
                ->name('social.callback');
        }

        if (Features::enabled(Features::passwordReset())) {
            Route::get('/forgot-password', [Auth\PasswordResetLinkController::class, 'create'])
                ->name('password.request');

            Route::post('/forgot-password', [Auth\PasswordResetLinkController::class, 'store'])
                ->middleware(['throttle:10,1', ValidateCaptcha::class])
                ->name('password.email');

            Route::get('/reset-password/{token}', [Auth\NewPasswordController::class, 'create'])
                ->name('password.reset');

            Route::post('/reset-password', [Auth\NewPasswordController::class, 'store'])
                ->middleware(['throttle:10,1', ValidateCaptcha::class])
                ->name('password.update');
        }
    });

if (Features::enabled(Features::logout())) {
    Route::prefix($prefix)
        ->middleware([...AuthPreset::webMiddleware(), 'auth:' . $guard])
        ->group(function () use ($prefix, $guard, $isPrimaryMount): void {
            Route::post('/logout', Auth\LogoutController::class)->name('logout');
        });
}

if (Features::enabled(Features::updatePasswords())) {
    Route::prefix($prefix)
        ->middleware([...AuthPreset::webMiddleware(), 'auth:' . $guard])
        ->group(function () use ($prefix, $guard, $isPrimaryMount): void {
            Route::get('/user/password', [Auth\UpdatePasswordController::class, 'create'])
                ->name('user-password.edit');

            Route::put('/user/password', [Auth\UpdatePasswordController::class, 'update'])
                ->name('user-password.update');
        });
}

if (Features::enabled(Features::updateProfileInformation())) {
    Route::prefix($prefix)
        ->middleware([...AuthPreset::webMiddleware(), 'auth:' . $guard])
        ->group(function () use ($prefix, $guard, $isPrimaryMount): void {
            Route::get('/user/profile-information', [Auth\UpdateProfileInformationController::class, 'create'])
                ->name('user-profile-information.edit');

            Route::put('/user/profile-information', [Auth\UpdateProfileInformationController::class, 'update'])
                ->name('user-profile-information.update');
        });
}

if (Features::enabled(Features::passkeys())) {
    Route::prefix($prefix)
        ->middleware([...AuthPreset::webMiddleware(), 'auth:' . $guard])
        ->group(function () use ($prefix, $guard, $isPrimaryMount): void {
            Route::get('/user/passkeys', [Auth\PasskeysController::class, 'index'])
                ->name('user-passkeys.index');
        });
}

if (Features::enabled(Features::emailVerification())) {
    Route::prefix($prefix)
        ->middleware([...AuthPreset::webMiddleware(), 'auth:' . $guard])
        ->group(function () use ($prefix, $guard, $isPrimaryMount): void {
            Route::get('/email/verify', Auth\EmailVerificationPromptController::class)
                ->name('verification.notice');

            Route::get('/email/verify/{id}/{hash}', Auth\VerifyEmailController::class)
                ->middleware(['signed', 'throttle:6,1'])
                ->name('verification.verify');

            Route::post('/email/verification-notification', [Auth\EmailVerificationNotificationController::class, 'store'])
                ->middleware(['throttle:6,1'])
                ->name('verification.send');
        });
}

/*
|--------------------------------------------------------------------------
| Endpoints Fortify owns, mounted under this package's prefix
|--------------------------------------------------------------------------
|
| Fortify's own route registration is switched off in PresetServiceProvider, because it mounts
| these at the application root where they escape this package's prefix, captcha and throttle.
| They are re-mounted here rather than reimplemented: the controllers below are Fortify's and
| Laravel Passkeys' own, so no credential or ceremony logic is duplicated -- only the mounting
| point moves.
|
| Passkeys is the one that was actually broken rather than merely misplaced: the management page
| is served from this package under the configured prefix, while the ceremony endpoints it posts
| to sat at the application root, so the two halves of the same feature lived at different paths.
|
*/

if (Features::enabled(Features::emailVerification())) {
    Route::prefix($prefix)
        ->middleware(AuthPreset::webMiddleware())
        ->group(function () use ($prefix, $guard, $isPrimaryMount): void {
            Route::get('/email/verify/{id}/{hash}', FortifyVerifyEmailController::class)
                ->middleware(['auth:' . $guard, 'signed', 'throttle:6,1'])
                ->name('verification.verify');

            Route::post('/email/verification-notification', [FortifyEmailVerificationNotificationController::class, 'store'])
                ->middleware(['auth:' . $guard, 'throttle:6,1'])
                ->name('verification.send');
        });
}

Route::prefix($prefix)
    ->middleware([...AuthPreset::webMiddleware(), 'auth:' . $guard])
    ->group(function () use ($prefix, $guard, $isPrimaryMount): void {
        Route::get('/user/confirm-password', [ConfirmablePasswordController::class, 'show'])
            ->name('password.confirm');

        Route::post('/user/confirm-password', [ConfirmablePasswordController::class, 'store'])
            ->name('password.confirm.store');

        Route::get('/user/confirmed-password-status', [ConfirmedPasswordStatusController::class, 'show'])
            ->name('password.confirmation');
    });

if (Features::enabled(Features::passkeys())) {
    Route::prefix($prefix)
        ->middleware([...AuthPreset::webMiddleware(), 'guest:' . $guard, 'throttle:10,1'])
        ->group(function () use ($prefix, $guard, $isPrimaryMount): void {
            Route::get('/passkeys/login/options', [PasskeyLoginController::class, 'index'])
                ->name('passkey.login-options');

            Route::post('/passkeys/login', [PasskeyLoginController::class, 'store'])
                ->name('passkey.login');
        });

    // The confirmation ceremony is how a user satisfies password.confirm with a passkey, so it
    // cannot itself sit behind password.confirm.
    Route::prefix($prefix)
        ->middleware([...AuthPreset::webMiddleware(), 'auth:' . $guard, 'throttle:10,1'])
        ->group(function () use ($prefix, $guard, $isPrimaryMount): void {
            Route::get('/passkeys/confirm/options', [PasskeyConfirmationController::class, 'index'])
                ->name('passkey.confirm-options');

            Route::post('/passkeys/confirm', [PasskeyConfirmationController::class, 'store'])
                ->name('passkey.confirm');
        });

    // Managing passkeys re-authenticates, mirroring Fortify: adding or deleting a credential is
    // a change to how the account can be signed into, so a hijacked session must not be enough.
    // The toggle is Fortify's own, so an application that has already turned it off keeps that.
    Route::prefix($prefix)
        ->middleware(array_values(array_filter([
            ...AuthPreset::webMiddleware(),
            'auth:' . $guard,
            config('fortify-options.passkeys.confirmPassword', true) ? 'password.confirm' : null,
            'throttle:10,1',
        ])))
        ->group(function () use ($prefix, $guard, $isPrimaryMount): void {
            Route::get('/user/passkeys/options', [PasskeyRegistrationController::class, 'index'])
                ->name('passkey.registration-options');

            Route::post('/user/passkeys', [PasskeyRegistrationController::class, 'store'])
                ->name('passkey.store');

            Route::delete('/user/passkeys/{passkey}', [PasskeyRegistrationController::class, 'destroy'])
                ->name('passkey.destroy');
        });
}
