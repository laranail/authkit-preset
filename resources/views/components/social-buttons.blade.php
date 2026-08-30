@php
    $providers = \Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset::enabledSocialProviders();
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
        @foreach ($providers as $provider)
            <a
                href="{{ route('social.redirect', ['provider' => $provider]) }}"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors"
            >
                @if ($provider === 'google')
                    <svg class="size-5" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    <span class="hidden sm:inline">Google</span>
                @elseif ($provider === 'apple')
                    <svg class="size-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.05 12.536c-.03-2.65 2.16-3.92 2.26-3.98-1.23-1.8-3.15-2.05-3.83-2.08-1.63-.16-3.18.96-4.01.96-.83 0-2.1-.94-3.45-.91-1.78.03-3.42 1.03-4.33 2.62-1.85 3.2-.47 7.94 1.33 10.54.88 1.27 1.93 2.7 3.31 2.65 1.33-.05 1.83-.86 3.44-.86 1.61 0 2.06.86 3.46.83 1.43-.02 2.34-1.3 3.21-2.58 1.01-1.48 1.43-2.91 1.45-2.98-.03-.01-2.78-1.07-2.81-4.24zM14.6 4.7c.73-.89 1.22-2.12 1.09-3.35-1.05.04-2.32.7-3.08 1.58-.68.78-1.27 2.03-1.11 3.23 1.17.09 2.36-.6 3.1-1.46z"/>
                    </svg>
                    <span class="hidden sm:inline">Apple</span>
                @elseif ($provider === 'x')
                    <svg class="size-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                    </svg>
                    <span class="hidden sm:inline">X</span>
                @elseif ($provider === 'linkedin')
                    <svg class="size-5" fill="#0A66C2" viewBox="0 0 24 24">
                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                    </svg>
                    <span class="hidden sm:inline">LinkedIn</span>
                @elseif ($provider === 'paypal')
                    <svg class="size-5" viewBox="0 0 24 24">
                        <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h7.46c2.57 0 4.578.543 5.69 1.81 1.01 1.15 1.304 2.42 1.012 4.287-.023.143-.047.288-.077.437-.983 5.05-4.349 6.797-8.647 6.797h-2.19c-.524 0-.968.382-1.025.9l-1.14 7.106z" fill="#253B80"/>
                        <path d="M20.16 7.534c-.01.063-.02.127-.033.19-1.28 6.576-5.669 8.816-11.244 8.816H6.01l-1.43 8.967a.483.483 0 0 0 .477.557h4.693c.42 0 .778-.305.844-.718l.044-.226.667-4.213.043-.23a.846.846 0 0 1 .843-.718h.53c3.43 0 6.115-1.392 6.9-5.47.327-1.693.158-3.108-.71-4.098a3.65 3.65 0 0 0-.751-.857z" fill="#179BD7"/>
                        <path d="M18.89 7.098a8.44 8.44 0 0 0-.857-.192c-.288-.048-.594-.086-.91-.112a10.57 10.57 0 0 0-.852-.04h-2.593a.846.846 0 0 0-.843.718l-1.06 6.675-.03.195a1.02 1.02 0 0 1 1.025-.9h2.19c4.298 0 7.664-1.747 8.647-6.797a8.547 8.547 0 0 0-.727-.547z" fill="#222D65"/>
                    </svg>
                    <span class="hidden sm:inline">PayPal</span>
                @endif
            </a>
        @endforeach
    </div>
@endif
