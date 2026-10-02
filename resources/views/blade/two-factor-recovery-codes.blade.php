<x-laranail-authkit-preset::dashboard-layout title="Recovery codes">
    <div class="py-12">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white px-8 py-10 sm:rounded-lg">
    <h1 class="text-2xl font-bold tracking-tight">Save your recovery codes</h1>
    <p class="mt-2 text-sm text-gray-600">Each code works once. Store these somewhere safe; they will not be shown again.</p>
    <ul class="my-6 grid grid-cols-2 gap-2 rounded-md bg-gray-100 p-4 font-mono text-sm">
        @foreach ($recoveryCodes as $code)
            <li>{{ $code }}</li>
        @endforeach
    </ul>
    <a href="{{ route('user-two-factor.index') }}" class="font-semibold text-indigo-600">Continue</a>
            </div>
        </div>
    </div>
</x-laranail-authkit-preset::dashboard-layout>
