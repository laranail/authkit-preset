@use(\Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset)

<x-laranail-authkit-preset::dashboard-layout :title="__('laranail/authkit-preset::messages.profile.title')">
    <div class="py-12">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white px-8 py-10 sm:rounded-lg">
                @php($profileErrors = $errors->getBag('updateProfileInformation'))
                <div class="mb-8">
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ __('laranail/authkit-preset::messages.profile.title') }}</h1>
                    <p class="mt-2 text-sm text-gray-600">{{ __('laranail/authkit-preset::messages.profile.intro') }}</p>
                </div>

                @if (session('status'))
                    <p role="status" class="mb-4 text-sm text-green-700">
                        {{ match (session('status')) {
                            'email-change-sent' => __('laranail/authkit-preset::messages.email_change.sent'),
                            'email-change-cancelled' => __('laranail/authkit-preset::messages.email_change.cancelled'),
                            'email-changed' => __('laranail/authkit-preset::messages.email_change.changed'),
                            default => __('laranail/authkit-preset::messages.email_change.saved'),
                        } }}
                    </p>
                @endif
                @foreach ($errors->getBag('emailChange')->all() as $error)
                    <p role="alert" class="mb-4 text-sm text-red-600">{{ $error }}</p>
                @endforeach

                <form method="POST" action="{{ route(AuthPreset::routeNameForCurrentRoute('user-profile-information.update')) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-laranail-authkit-preset::label for="name" :value="__('laranail/authkit-preset::messages.profile.name')" />
                        <div class="mt-2">
                            <x-laranail-authkit-preset::input
                                id="name"
                                name="name"
                                type="text"
                                :value="old('name', $user->name)"
                                autofocus
                                autocomplete="name"
                                placeholder="John Doe"
                                :error="$profileErrors->has('name')"
                            />
                        </div>
                        <x-laranail-authkit-preset::input-error :message="$profileErrors->first('name')" />
                    </div>

                    <div>
                        <x-laranail-authkit-preset::label for="email" :value="__('laranail/authkit-preset::messages.profile.email')" />
                        <div class="mt-2">
                            <x-laranail-authkit-preset::input
                                id="email"
                                name="email"
                                type="email"
                                :value="old('email', $user->email)"
                                autocomplete="email"
                                placeholder="you@example.com"
                                :error="$profileErrors->has('email')"
                            />
                        </div>
                        <x-laranail-authkit-preset::input-error :message="$profileErrors->first('email')" />
                        <p class="mt-2 text-sm text-gray-600">{{ __('laranail/authkit-preset::messages.email_change.help') }}</p>
                    </div>

                    <div>
                        <button
                            type="submit"
                            class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                        >
                            {{ __('laranail/authkit-preset::messages.profile.submit') }}
                        </button>
                    </div>
                </form>

                @if ($pendingEmailChange)
                    <section class="mt-8 space-y-3 border-t border-gray-200 pt-6" aria-label="{{ __('laranail/authkit-preset::messages.email_change.confirm_title') }}">
                        <p role="status" class="text-sm text-gray-700">{{ __('laranail/authkit-preset::messages.email_change.pending', ['email' => $pendingEmailChange['email']]) }}</p>
                        <p class="text-sm text-gray-600">{{ __('laranail/authkit-preset::messages.email_change.expires', ['time' => date('Y-m-d H:i T', $pendingEmailChange['expires_at'])]) }}</p>
                        <form method="POST" action="{{ route(AuthPreset::routeNameForCurrentRoute('user-email-change.resend')) }}">
                            @csrf
                            <button class="text-sm font-semibold text-indigo-600">{{ __('laranail/authkit-preset::messages.email_change.resend') }}</button>
                        </form>
                        <form method="POST" action="{{ route(AuthPreset::routeNameForCurrentRoute('user-email-change.destroy')) }}">
                            @csrf
                            @method('DELETE')
                            <button class="text-sm font-semibold text-red-600">{{ __('laranail/authkit-preset::messages.email_change.cancel') }}</button>
                        </form>
                    </section>
                @endif
            </div>
        </div>
    </div>
</x-laranail-authkit-preset::dashboard-layout>
