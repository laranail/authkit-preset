<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AuthKit\Preset\Support;

use Illuminate\Routing\Router;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Routing\UrlGenerator;

/** Resolve browser verification links when used, even inside Fortify's singleton profile action. */
final class EmailChangeConfiguration implements Repository
{
    public function __construct(
        private Repository $config,
        private UrlGenerator $urls,
        private Router $router,
    ) {}

    public function get($key, $default = null): mixed
    {
        $value = $this->config->get($key, $default);
        if ($key === 'laranail.authkit.email_change.verification_url' && $value === null) {
            $route = AuthPreset::routeNameForCurrentRoute('user-email-change.verify');
            if ($this->router->has($route)) {
                return $this->urls->route($route) . '?token={token}';
            }
        }

        return $value;
    }

    public function has($key): bool
    {
        return $this->config->has($key);
    }

    public function all(): array
    {
        return $this->config->all();
    }

    public function set($key, $value = null): void
    {
        $this->config->set($key, $value);
    }

    public function prepend($key, $value): void
    {
        $this->config->prepend($key, $value);
    }

    public function push($key, $value): void
    {
        $this->config->push($key, $value);
    }
}
