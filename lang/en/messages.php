<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| laranail/authkit-preset — user-facing strings
|--------------------------------------------------------------------------
|
| Referenced as laranail-authkit-preset::messages.*, matching the package's
| view namespace. Publish with:
|
|     php artisan vendor:publish --tag=laranail::authkit-preset-lang
|
| Published files land in lang/vendor/laranail-authkit-preset, which is where
| Laravel looks for overrides of this namespace.
|
*/

return [

    'login' => [
        'title'      => 'Sign in to your account',
        'no_account' => "Don't have an account?",
        'register'   => 'Register',
        'email'      => 'Email address',
        'password'   => 'Password',
        'remember'   => 'Remember me',
        'forgot'     => 'Forgot password?',
        'submit'     => 'Sign in',
        'passkey'    => 'Sign in with a passkey',
        'failed'     => 'Invalid credentials.',
        'throttled'  => 'Too many attempts.',
    ],

    'register' => [
        'title'            => 'Create your account',
        'have_account'     => 'Already have an account?',
        'sign_in'          => 'Sign in',
        'name'             => 'Full name',
        'email'            => 'Email address',
        'password'         => 'Password',
        'confirm_password' => 'Confirm password',
        'submit'           => 'Create account',
    ],

    'passwords' => [
        'forgot_title'  => 'Reset your password',
        'forgot_intro'  => 'Tell us the address on your account and we will send a reset link.',
        'forgot_submit' => 'Email password reset link',
        'reset_title'   => 'Set a new password',
        'reset_submit'  => 'Reset password',
        'update_title'  => 'Update your password',
        'update_intro'  => 'Choose a new password for your account.',
        'current'       => 'Current password',
        'new'           => 'New password',
        'confirm'       => 'Confirm new password',
        'update_submit' => 'Update password',
    ],

    'profile' => [
        'title'  => 'Update your profile',
        'intro'  => 'Keep your account information up to date.',
        'name'   => 'Name',
        'email'  => 'Email address',
        'submit' => 'Save',
    ],

    'verification' => [
        'title'  => 'Verify your email',
        'intro'  => 'We sent a verification link to your address. Follow it to finish setting up your account.',
        'resend' => 'Resend verification email',
        'sent'   => 'A new verification link has been sent.',
        'logout' => 'Sign out',
    ],

    'passkeys' => [
        'title'      => 'Manage your passkeys',
        'intro'      => 'Use a passkey to sign in without a password.',
        'add_title'  => 'Add a passkey',
        'add_intro'  => 'Choose a name and confirm your password to register a new passkey.',
        'name'       => 'Passkey name',
        'current'    => 'Current password',
        'register'   => 'Register a passkey',
        'registered' => 'Registered passkeys',
        'none'       => 'No passkeys registered yet.',
        'added'      => 'Added :date',
        'remove'     => 'Remove',
        'created'    => 'Passkey registered successfully.',
    ],

    'social' => [
        'divider' => 'Or continue with',
        'failed'  => 'Social authentication failed.',
    ],

    'dashboard' => [
        'title'    => 'Dashboard',
        'welcome'  => 'Welcome back, :name.',
        'profile'  => 'Profile',
        'passkeys' => 'Passkeys',
        'logout'   => 'Sign out',
    ],

];
