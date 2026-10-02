@props(['title' => 'Dashboard'])

<x-laranail-authkit-preset::layout
    :$title
    body-class="bg-gray-100 font-sans text-gray-900 antialiased"
    main-class="contents"
    content-class="contents"
    card-class="contents"
>
    <nav class="border-b border-gray-100 bg-white">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-8">
                <a href="{{ route('dashboard') }}" class="text-sm font-medium text-gray-900">Dashboard</a>
                @if (\Illuminate\Support\Facades\Route::has(\Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset::routeName('user-profile-information.edit')))
                    <a href="{{ route('user-profile-information.edit') }}" class="text-sm font-medium text-gray-500 hover:text-gray-900">Profile</a>
                @endif
                @if (\Simtabi\Laranail\AuthKit\Preset\Features::enabled(\Simtabi\Laranail\AuthKit\Preset\Features::passkeys()) && \Illuminate\Support\Facades\Route::has(\Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset::routeName('user-passkeys.index')))
                    <a href="{{ route('user-passkeys.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-900">Passkeys</a>
                @endif
                @if (\Simtabi\Laranail\AuthKit\Preset\Features::enabled(\Simtabi\Laranail\AuthKit\Preset\Features::twoFactorAuthentication()) && \Illuminate\Support\Facades\Route::has(\Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset::routeName('user-two-factor.index')))
                    <a href="{{ route('user-two-factor.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-900">Two-factor</a>
                @endif
                @if (class_exists(\Simtabi\Laranail\AuthKit\Social\Providers\SocialServiceProvider::class) && \Illuminate\Support\Facades\Route::has(\Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset::routeName('user-social-accounts.index')))
                    <a href="{{ route(\Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset::routeName('user-social-accounts.index')) }}" class="text-sm font-medium text-gray-500 hover:text-gray-900">Social Accounts</a>
                @endif
            </div>
            <details class="relative">
                <summary class="flex cursor-pointer list-none items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    <span>{{ auth()->user()->name }}</span>
                    <svg class="size-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 0 1 1.06 0L10 11.19l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                    </svg>
                </summary>
                @if (\Simtabi\Laranail\AuthKit\Preset\Features::enabled(\Simtabi\Laranail\AuthKit\Preset\Features::logout()) && \Illuminate\Support\Facades\Route::has(\Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset::routeName('logout')))
                    <div class="absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black/5">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100">Log out</button>
                        </form>
                    </div>
                @endif
            </details>
        </div>
    </nav>

    {{ $slot }}
</x-laranail-authkit-preset::layout>
