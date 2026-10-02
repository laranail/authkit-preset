<x-laranail-authkit-preset::dashboard-layout title="Confirm password">
    <div class="py-12">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white px-8 py-10 sm:rounded-lg">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Confirm your password</h1>
                <p class="mt-2 text-sm text-gray-600">For your security, confirm your password before changing authentication settings.</p>

                <form method="POST" action="{{ route('password.confirm.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <x-laranail-authkit-preset::label for="password" value="Password" />
                    <x-laranail-authkit-preset::input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        autofocus
                        required
                        :error="$errors->has('password')"
                    />
                    <x-laranail-authkit-preset::input-error :message="$errors->first('password')" />
                    <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                        Confirm password
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-laranail-authkit-preset::dashboard-layout>
