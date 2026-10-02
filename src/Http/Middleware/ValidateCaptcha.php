<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AuthKit\Preset\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Simtabi\Laranail\AuthKit\Http\Requests\RegisterRequest;
use Simtabi\Laranail\AuthKit\Http\Requests\AttemptEmailPasswordLoginRequest;
use Simtabi\Laranail\Captcha\Rules\Captcha;
use Simtabi\Laranail\AuthKit\Preset\Features;

class ValidateCaptcha
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! Features::enabled(Features::botProtection())) {
            return $next($request);
        }

        // Let ordinary form validation report first. Otherwise a missing captcha response
        // masks required email/password/name errors and makes the form look as though its
        // inline validation is broken. The downstream controller still cannot authenticate
        // or create a user until the captcha check below passes.
        $fieldRules = match (true) {
            str_ends_with((string) $request->route()?->getName(), 'login.store') =>
                (new AttemptEmailPasswordLoginRequest)->rules(),
            str_ends_with((string) $request->route()?->getName(), 'register.store') =>
                RegisterRequest::rulesFor(),
            default => [],
        };

        if ($fieldRules !== []) {
            $fieldRules = array_diff_key($fieldRules, [
                (string) config('laranail.captcha.response_field', 'captcha') => true,
                (string) config('laranail.authkit.turnstile.input', 'cf-turnstile-response') => true,
            ]);

            $fields = Validator::make($request->all(), $fieldRules);

            if ($fields->fails()) {
                $fields->validate();
            }
        }

        Validator::make(
            data: $request->all(),
            // Captcha is an implicit rule: it finds the canonical `captcha` field, the
            // provider's vendor field (Turnstile uses `cf-turnstile-response`), or the
            // self-hosted challenge fields. A separate `required` rule only checks the
            // canonical name and rejects valid vendor responses before this rule can read them.
            rules: ['captcha' => [new Captcha]],
        )->validate();

        return $next($request);
    }
}
