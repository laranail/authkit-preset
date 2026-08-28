<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AuthKit\Preset\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stop authenticated pages being replayed after sign-out.
 *
 * Laravel's default is `no-cache, private`, which permits a browser to keep the rendered page
 * and re-serve it from the back/forward cache. On a shared machine that means signing out and
 * handing over the laptop still leaves the previous user's account pages one Back press away:
 * the page is never re-requested, so no amount of server-side session invalidation is consulted.
 *
 * `no-store` is the directive that forbids retaining the response at all, and is what OWASP
 * recommends for authenticated pages. It is applied only to responses for an authenticated
 * request, so public pages stay cacheable.
 */
class PreventAuthenticatedPageCaching
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if ($request->user() === null) {
            return $response;
        }

        // Downloads and other streamed responses are excluded: they are frequently large, and a
        // client that cannot buffer one has no way to re-request it.
        if ($response->isRedirection()) {
            return $response;
        }

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
