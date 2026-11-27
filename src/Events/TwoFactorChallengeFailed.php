<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AuthKit\Preset\Events;

/** Immutable audit metadata; never contains credentials or model instances. */
final readonly class TwoFactorChallengeFailed
{
    public function __construct(
        public string $userType,
        public string $userId,
        public string $guard,
    ) {}
}
