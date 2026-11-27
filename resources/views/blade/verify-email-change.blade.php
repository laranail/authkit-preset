@use(\Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset)

<x-laranail-authkit-preset::dashboard-layout :title="__('laranail/authkit-preset::messages.email_change.confirm_title')">
    <div class="mx-auto max-w-2xl px-4 py-12">
        <div class="space-y-6 rounded-lg bg-white p-8">
            <h1 class="text-2xl font-bold">{{ __('laranail/authkit-preset::messages.email_change.confirm_title') }}</h1>
            @if ($pending && $token)
                <p>{{ __('laranail/authkit-preset::messages.email_change.confirm_intro', ['email' => $pending['email']]) }}</p>
                <form method="POST" action="{{ route(AuthPreset::routeNameForCurrentRoute('user-email-change.confirm')) }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}" />
                    <button class="rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white">{{ __('laranail/authkit-preset::messages.email_change.confirm') }}</button>
                </form>
            @else
                <p role="alert">{{ __('laranail/authkit-preset::messages.email_change.invalid') }}</p>
            @endif
            <a class="text-sm font-semibold text-indigo-600" href="{{ route(AuthPreset::routeNameForCurrentRoute('user-profile-information.edit')) }}">{{ __('laranail/authkit-preset::messages.email_change.back') }}</a>
        </div>
    </div>
</x-laranail-authkit-preset::dashboard-layout>
