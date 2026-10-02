<x-laranail-authkit-preset::layout title="Two-factor verification">
    <h1 class="text-2xl font-bold tracking-tight">Verify your identity</h1>
    <p class="mt-2 text-sm text-gray-600">Enter the six-digit code from your authenticator app, or use a recovery code.</p>

    <form method="POST" action="{{ route('two-factor.challenge.verify') }}" class="mt-6 space-y-4">
        @csrf
        <label for="code" class="block text-sm font-medium">Authenticator or recovery code</label>
        <input id="code" name="code" value="{{ old('code') }}" required autofocus autocomplete="one-time-code" inputmode="numeric" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
        <x-laranail-authkit-preset::input-error :message="$errors->first('code')" />
        <button class="w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white">Verify and sign in</button>
    </form>
</x-laranail-authkit-preset::layout>
