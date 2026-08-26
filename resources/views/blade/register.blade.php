<x-laranail-authkit-preset::layout title="Register">
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold tracking-tight text-gray-900">{{ __('laranail-authkit-preset::messages.register.title') }}</h2>
        <p class="mt-2 text-sm text-gray-600">
            {{ __('laranail-authkit-preset::messages.register.have_account') }}
            @if (\Simtabi\Laranail\AuthKitPreset\Features::enabled(\Simtabi\Laranail\AuthKitPreset\Features::login()))
                <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-500">
                    {{ __('laranail-authkit-preset::messages.register.sign_in') }}
                </a>
            @endif
        </p>
    </div>

    <x-laranail-authkit-preset::social-buttons />

    <form method="POST" action="{{ route('register.store') }}" class="space-y-6">
        @csrf

        <div>
            <x-laranail-authkit-preset::label for="name" value="{{ __('laranail-authkit-preset::messages.register.name') }}" />
            <div class="mt-2">
                <x-laranail-authkit-preset::input
                    id="name"
                    name="name"
                    type="text"
                    :value="old('name')"
                    autofocus
                    autocomplete="name"
                    placeholder="John Doe"
                    :error="$errors->has('name')"
                />
            </div>
            <x-laranail-authkit-preset::input-error :message="$errors->first('name')" />
        </div>

        <div>
            <x-laranail-authkit-preset::label for="email" value="{{ __('laranail-authkit-preset::messages.register.email') }}" />
            <div class="mt-2">
                <x-laranail-authkit-preset::input
                    id="email"
                    name="email"
                    type="email"
                    :value="old('email')"
                    autocomplete="email"
                    placeholder="you@example.com"
                    :error="$errors->has('email')"
                />
            </div>
            <x-laranail-authkit-preset::input-error :message="$errors->first('email')" />
        </div>

        <div>
            <x-laranail-authkit-preset::label for="password" value="{{ __('laranail-authkit-preset::messages.register.password') }}" />
            <div class="mt-2">
                <x-laranail-authkit-preset::input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="new-password"
                    placeholder="At least 8 characters"
                    :error="$errors->has('password')"
                />
            </div>
            <x-laranail-authkit-preset::input-error :message="$errors->first('password')" />
        </div>

        <div>
            <x-laranail-authkit-preset::label for="password_confirmation" value="{{ __('laranail-authkit-preset::messages.register.confirm_password') }}" />
            <div class="mt-2">
                <x-laranail-authkit-preset::input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    placeholder="Re-enter your password"
                    :error="$errors->has('password_confirmation')"
                />
            </div>
            <x-laranail-authkit-preset::input-error :message="$errors->first('password_confirmation')" />
        </div>

        @if (\Simtabi\Laranail\AuthKitPreset\Features::enabled(\Simtabi\Laranail\AuthKitPreset\Features::botProtection()))
            <x-captcha />
        @endif

        <div>
            <button
                type="submit"
                class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            >
                {{ __('laranail-authkit-preset::messages.register.submit') }}
            </button>
        </div>
    </form>
</x-laranail-authkit-preset::layout>
