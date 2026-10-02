<x-laranail-authkit-preset::dashboard-layout title="Two-factor authentication">
    <div class="py-12">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white px-8 py-10 sm:rounded-lg">
    <h1 class="text-2xl font-bold tracking-tight">Two-factor authentication</h1>
    <p class="mt-2 text-sm text-gray-600">Use any authenticator app that supports standard TOTP, including 1Password and Google Authenticator.</p>

    @if (session('status') === 'two-factor-disabled')
        <p class="mt-4 rounded-md bg-green-50 p-3 text-sm text-green-700">Two-factor authentication is disabled.</p>
    @endif

    @if ($enabled)
        <p class="mt-6 text-sm text-green-700">Authenticator-based two-factor authentication is enabled. {{ $recoveryCodeCount }} recovery codes remain.</p>
        <form method="POST" action="{{ route('user-two-factor.recovery-codes') }}" class="mt-6 space-y-3">
            @csrf
            <label for="rotate-code" class="block text-sm font-medium">Verify to replace recovery codes</label>
            <input id="rotate-code" name="code" required autocomplete="one-time-code" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
            <x-laranail-authkit-preset::input-error :message="$errors->first('code')" />
            <button class="rounded-md border border-gray-300 px-3 py-2 text-sm font-semibold">Generate new recovery codes</button>
        </form>
        <form method="POST" action="{{ route('user-two-factor.disable') }}" class="mt-6 space-y-3">
            @csrf
            <label for="disable-code" class="block text-sm font-medium">Verify to disable</label>
            <input id="disable-code" name="code" required autocomplete="one-time-code" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
            <x-laranail-authkit-preset::input-error :message="$errors->first('code')" />
            <button class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white">Disable two-factor authentication</button>
        </form>
    @else
        <form method="POST" action="{{ route('user-two-factor.begin') }}" class="mt-6">
            @csrf
            <button class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white">Set up authenticator app</button>
        </form>
    @endif
            </div>
        </div>
    </div>
</x-laranail-authkit-preset::dashboard-layout>
