<x-laranail-authkit-preset::dashboard-layout title="Set up an authenticator">
    <div class="py-12">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white px-8 py-10 sm:rounded-lg">
    <h1 class="text-2xl font-bold tracking-tight">Set up an authenticator app</h1>
    <p class="mt-2 text-sm text-gray-600">Scan this QR code with an authenticator app, then enter its current six-digit code.</p>

    <div class="my-6 flex justify-center">{!! $qrCodeSvg !!}</div>
    <p class="text-sm text-gray-700">If you cannot scan the QR code, enter this key manually:</p>
    <code class="mt-2 block break-all rounded bg-gray-100 p-3 text-sm">{{ $secret }}</code>

    <form method="POST" action="{{ route('user-two-factor.confirm') }}" class="mt-6 space-y-4">
        @csrf
        <label for="code" class="block text-sm font-medium">Six-digit code</label>
        <input id="code" name="code" required autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
        <x-laranail-authkit-preset::input-error :message="$errors->first('code')" />
        <button class="w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white">Confirm and enable</button>
    </form>
            </div>
        </div>
    </div>
</x-laranail-authkit-preset::dashboard-layout>
