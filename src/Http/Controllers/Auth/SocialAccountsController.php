<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AuthKit\Preset\Http\Controllers\Auth;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Simtabi\Laranail\AuthKit\Preset\Support\AuthPreset;
use Simtabi\Laranail\AuthKit\Social\Enums\SocialProvider;
use Simtabi\Laranail\AuthKit\Social\Services\SocialAccountService;
use Simtabi\Laranail\AuthKit\Contracts\IdentityProviderRegistryInterface;
use Simtabi\Laranail\AuthKit\Social\Contracts\UnlinkSocialAccountInterface;

/**
 * Lists a user's linked providers and removes one.
 *
 * `canUnlink` is passed to the view so the control can be disabled with an explanation, rather than
 * offered and then refused — removing the only way into an account is a thing to prevent, not to
 * report after the fact.
 */
class SocialAccountsController
{
    public function index(Request $request, SocialAccountService $accounts): View
    {
        $user = $request->user();

        return view(AuthPreset::view('social-accounts'), [
            'accounts' => $accounts->forUser($user)->map(fn ($social): array => [
                'slug'       => is_string($social->provider) ? $social->provider : $social->provider->slug(),
                'label'      => is_string($social->provider) ? $social->provider : $social->provider->label(),
                'email'      => $social->email,
                'can_unlink' => ! is_string($social->provider) && $accounts->canUnlink($user, $social->provider),
            ]),
        ]);
    }

    public function destroy(
        Request $request,
        string $provider,
        UnlinkSocialAccountInterface $unlink,
        IdentityProviderRegistryInterface $registry,
    ): RedirectResponse {
        $resolved = SocialProvider::tryFrom($provider) ?? $registry->get($provider);

        if ($resolved === null) {
            abort(404);
        }

        if (! $unlink->execute(user: $request->user(), provider: $resolved)) {
            return back()->withErrors([
                'provider' => 'That is the only way you can sign in, so it cannot be removed. Add another sign-in method first.',
            ]);
        }

        return back()->with('status', 'social-account-unlinked');
    }
}
