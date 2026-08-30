@php
    $providers = \Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset::socialProviders();
@endphp

@if (count($providers) > 0)
    <div class="relative my-6">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-200"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="bg-white px-4 text-gray-500">Or continue with</span>
        </div>
    </div>

    <div class="grid grid-cols-{{ min(count($providers), 3) }} gap-3 mb-6">
        {{--
            One branch per provider used to live here, which meant adding a provider was an edit to
            this file and a provider contributed by a sub-package could never render at all. Label,
            icon and classes now come from the provider's `ui` config block.
        --}}
        @foreach ($providers as $provider)
            <a
                href="{{ route(\Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset::routeName('social.redirect'), ['provider' => $provider['slug']]) }}"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors {{ $provider['class'] }}"
                aria-label="Continue with {{ $provider['label'] }}"
            >
                {{-- A provider whose icon view is missing still gets a usable button. --}}
                @includeWhen(view()->exists($provider['icon']), $provider['icon'])

                <span class="hidden sm:inline">{{ $provider['label'] }}</span>
            </a>
        @endforeach
    </div>
@endif
