@use(\Simtabi\Laranail\AuthKit\Support\AuthKit)

@php
    // Read once, used six times below. Locals rather than repeated static calls, and declared in
    // this file rather than shared by a composer, so nothing here depends on what the including
    // code happens to have set.
    $loginField = AuthKit::loginField();
    $emailOnly  = AuthKit::loginIsEmailOnly();
@endphp

<x-laranail-authkit-preset::layout title="Login">
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold tracking-tight text-gray-900">{{ __('laranail/authkit-preset::messages.login.title') }}</h2>
        <p class="mt-2 text-sm text-gray-600">
            @if (\Simtabi\Laranail\AuthKit\Preset\Features::enabled(\Simtabi\Laranail\AuthKit\Preset\Features::registration()))
                {{ __('laranail/authkit-preset::messages.login.no_account') }}
                <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:text-indigo-500">
                    {{ __('laranail/authkit-preset::messages.login.register') }}
                </a>
            @endif
        </p>
    </div>

    <x-laranail-authkit-preset::social-buttons />

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        {{--
            The identifier field follows AuthKit, it does not assume an address.

            `laranail.authkit.login.field` names the input the request reads, and
            `AttemptEmailPasswordLoginRequest` drops its `email` rule as soon as more than one
            identifier is configured. Three things have to move together or the feature is
            unreachable from this form: a form posting `email` to a backend reading `username`
            sends nothing the validator can see; `type="email"` makes the browser reject a username
            before the request is ever made; and `old()`/`$errors` keyed on the wrong name silently
            lose the submitted value on a failed attempt.
        --}}
        <div>
            <x-laranail-authkit-preset::label
                for="{{ $loginField }}"
                value="{{ $emailOnly
                    ? __('laranail/authkit-preset::messages.login.email')
                    : __('laranail/authkit-preset::messages.login.identifier') }}"
            />
            <div class="mt-2">
                <x-laranail-authkit-preset::input
                    id="{{ $loginField }}"
                    name="{{ $loginField }}"
                    type="{{ $emailOnly ? 'email' : 'text' }}"
                    :value="old($loginField)"
                    autofocus
                    autocomplete="{{ $emailOnly ? 'email webauthn' : 'username webauthn' }}"
                    placeholder="{{ $emailOnly
                        ? __('laranail/authkit-preset::messages.login.email_placeholder')
                        : __('laranail/authkit-preset::messages.login.identifier_placeholder') }}"
                    :error="$errors->has($loginField)"
                />
            </div>
            <x-laranail-authkit-preset::input-error :message="$errors->first($loginField)" />
        </div>

        <div>
            <x-laranail-authkit-preset::label for="password" value="{{ __('laranail/authkit-preset::messages.login.password') }}" />
            <div class="mt-2">
                <x-laranail-authkit-preset::input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    :error="$errors->has('password')"
                />
            </div>
            <x-laranail-authkit-preset::input-error :message="$errors->first('password')" />
        </div>

        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <input
                    id="remember"
                    name="remember"
                    type="checkbox"
                    value="1"
                    {{ old('remember') ? 'checked' : '' }}
                    class="size-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                >
                <label for="remember" class="ml-3 block text-sm text-gray-700">{{ __('laranail/authkit-preset::messages.login.remember') }}</label>
            </div>

            @if (\Simtabi\Laranail\AuthKit\Preset\Features::enabled(\Simtabi\Laranail\AuthKit\Preset\Features::passwordReset()))
                <a href="{{ route('password.request') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                    {{ __('laranail/authkit-preset::messages.login.forgot') }}
                </a>
            @endif
        </div>

        @if (\Simtabi\Laranail\AuthKit\Preset\Features::enabled(\Simtabi\Laranail\AuthKit\Preset\Features::botProtection()))
            <x-captcha size="flexible"  />
            <x-laranail-authkit-preset::input-error :message="$errors->first('captcha')" />
        @endif

        <div>
            <button
                type="submit"
                class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            >
                {{ __('laranail/authkit-preset::messages.login.submit') }}
            </button>
        </div>
    </form>

    @if (\Simtabi\Laranail\AuthKit\Preset\Features::enabled(\Simtabi\Laranail\AuthKit\Preset\Features::passkeys()))
        <div
            class="mt-6"
            data-passkey-login
            data-passkey-login-options-url="{{ route('passkey.login-options') }}"
            data-passkey-login-url="{{ route('passkey.login') }}"
        >
            <p class="mb-3 text-sm text-red-600" data-passkey-error hidden></p>
            <button
                type="button"
                class="flex w-full justify-center rounded-md border border-indigo-600 px-3 py-2 text-sm font-semibold text-indigo-600 hover:bg-indigo-50"
                data-passkey-login-button
            >
                {{ __('laranail/authkit-preset::messages.login.passkey') }}
            </button>
        </div>
    @endif
</x-laranail-authkit-preset::layout>
