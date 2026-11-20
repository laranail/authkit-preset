<?php

declare(strict_types=1);

return [
    'invalid_skip'         => 'Unknown installation step(s): :steps. Supported steps: :supported.',
    'skipped'              => 'Skipped [:step].',
    'planned'              => 'Would run [:step]: :description',
    'dry_run_complete'     => 'Dry run complete. No files changed.',
    'model'                => 'Configure authentication model :model.',
    'config'               => 'Publish AuthKit and preset configuration and apply the selected features.',
    'tailwind'             => 'Add the package view source to resources/css/app.css.',
    'passkeys'             => 'Add @laravel/passkeys to package.json and install the JavaScript client.',
    'two_factor_migration' => 'Publish the two-factor authentication migration.',
    'sanctum_migration'    => 'Publish the Sanctum token migration.',
    'passkey_migration'    => 'Publish the passkeys migration.',
    'routes'               => 'Publish the preset route files.',
    'views'                => 'Publish the preset Blade views.',
    'environment'          => 'Append missing session and selected captcha variables to .env and .env.example.',
];
