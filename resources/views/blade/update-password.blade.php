<x-laranail-authkit-preset::layout title="Update password">
    @php($passwordErrors = $errors->getBag('updatePassword'))
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold tracking-tight text-gray-900">Update your password</h2>
        <p class="mt-2 text-sm text-gray-600">Choose a new password for your account.</p>
    </div>

    <form method="POST" action="{{ route('user-password.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div>
            <x-laranail-authkit-preset::label for="current_password" value="Current password" />
            <div class="mt-2">
                <x-laranail-authkit-preset::input
                    id="current_password"
                    name="current_password"
                    type="password"
                    autofocus
                    autocomplete="current-password"
                    placeholder="Enter your current password"
                    :error="$passwordErrors->has('current_password')"
                />
            </div>
            <x-laranail-authkit-preset::input-error :message="$passwordErrors->first('current_password')" />
        </div>

        <div>
            <x-laranail-authkit-preset::label for="password" value="New password" />
            <div class="mt-2">
                <x-laranail-authkit-preset::input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="new-password"
                    placeholder="Enter your new password"
                    :error="$passwordErrors->has('password')"
                />
            </div>
            <x-laranail-authkit-preset::input-error :message="$passwordErrors->first('password')" />
        </div>

        <div>
            <x-laranail-authkit-preset::label for="password_confirmation" value="Confirm new password" />
            <div class="mt-2">
                <x-laranail-authkit-preset::input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    placeholder="Re-enter your new password"
                    :error="$passwordErrors->has('password_confirmation')"
                />
            </div>
            <x-laranail-authkit-preset::input-error :message="$passwordErrors->first('password_confirmation')" />
        </div>

        <div>
            <button
                type="submit"
                class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            >
                Update password
            </button>
        </div>
    </form>
</x-laranail-authkit-preset::layout>