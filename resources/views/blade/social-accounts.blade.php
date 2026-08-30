<x-laranail-authkit-preset::dashboard-layout>
    <div class="mx-auto max-w-2xl px-4 py-10">
        <h1 class="text-xl font-semibold text-gray-900">Connected accounts</h1>
        <p class="mt-1 text-sm text-gray-500">Sign-in methods linked to your account.</p>

        @if (session('status') === 'social-account-unlinked')
            <div class="mt-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800">
                That account has been disconnected.
            </div>
        @endif

        @error('provider')
            <div class="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $message }}</div>
        @enderror

        <ul class="mt-6 divide-y divide-gray-200 rounded-lg border border-gray-200">
            @forelse ($accounts as $account)
                <li class="flex items-center justify-between gap-4 px-4 py-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900">{{ $account['label'] }}</p>
                        @if ($account['email'])
                            <p class="truncate text-sm text-gray-500">{{ $account['email'] }}</p>
                        @endif
                    </div>

                    @if ($account['can_unlink'])
                        <form
                            method="POST"
                            action="{{ route(\Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset::routeName('user-social-accounts.destroy'), ['provider' => $account['slug']]) }}"
                        >
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500">
                                Disconnect
                            </button>
                        </form>
                    @else
                        {{-- Explained rather than offered and refused: this is the only way in. --}}
                        <span class="text-sm text-gray-400" title="Add another sign-in method first">
                            Only sign-in method
                        </span>
                    @endif
                </li>
            @empty
                <li class="px-4 py-6 text-sm text-gray-500">No connected accounts.</li>
            @endforelse
        </ul>
    </div>
</x-laranail-authkit-preset::dashboard-layout>
