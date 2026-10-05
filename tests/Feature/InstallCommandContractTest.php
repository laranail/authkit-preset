<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command;
use Simtabi\Laranail\AuthKit\Preset\Commands\InstallCommand;
use Simtabi\Laranail\Package\Tools\Commands\Concerns\ReadsOptions;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\InteractsWithConsoleWriter;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\InteractsWithConsoleServices;
use Simtabi\Laranail\Package\Tools\Commands\InstallCommand as PackageToolsInstallCommand;

/**
 * The install command's observable surface, pinned.
 *
 * Its base class moved from laranail/console's `Command` to laranail/package-tools'
 * `InstallCommand`, with console's display API kept by `use`-ing its two traits. A base swap is
 * where a name, an option, the listing visibility or a line of output changes without anyone
 * deciding it should, so every one of those is asserted here against what the command did before.
 */
function authkitPresetInstallCommand(): Command
{
    $command = Artisan::all()['laranail::authkit-preset.install'] ?? null;

    expect($command)->toBeInstanceOf(InstallCommand::class);

    /** @var Command $command */
    return $command;
}

it('keeps its name, aliases, description and listing visibility', function (): void {
    $command = authkitPresetInstallCommand();

    expect($command->getName())->toBe('laranail::authkit-preset.install')
        ->and($command->getAliases())->toBe([])
        ->and($command->getDescription())->toBe('Install the laranail/authkit-preset Blade resources')
        ->and($command->isHidden())->toBeFalse();
});

it('keeps its own options, their value modes, and no arguments', function (): void {
    $definition = authkitPresetInstallCommand()->getNativeDefinition();

    expect(array_keys($definition->getOptions()))->toBe([
        'stack',
        'api',
        'password-reset',
        'email-verification',
        'passkeys',
        'two-factor-authentication',
        'bot-protection',
        'model',
        'publish-routes',
        'publish-views',
        'force',
    ])->and($definition->getArguments())->toBe([]);

    $valued = ['stack', 'model'];

    foreach ($definition->getOptions() as $name => $option) {
        $takesValue = in_array($name, $valued, true);

        expect($option->acceptValue())->toBe($takesValue, "--{$name} value mode")
            ->and($option->isValueOptional())->toBe($takesValue, "--{$name} optional value")
            ->and($option->getShortcut())->toBeNull();
    }

    expect($definition->getOption('stack')->getDescription())->toBe('The frontend stack to install')
        ->and($definition->getOption('force')->getDescription())->toBe('Overwrite existing published files');
});

it('refuses an unsupported stack with exit code 1', function (): void {
    $this->artisan('laranail::authkit-preset.install', ['--stack' => 'vue', '--no-interaction' => true])
        ->expectsOutputToContain('Only the [blade] stack is currently supported.')
        ->doesntExpectOutputToContain('laranail/authkit-preset is ready.')
        ->assertExitCode(1);
});

it('refuses a model no Eloquent auth provider configures with exit code 1', function (): void {
    $this->artisan('laranail::authkit-preset.install', [
        '--stack'          => 'blade',
        '--api'            => true,
        '--model'          => 'App\\Models\\Nobody',
        '--no-interaction' => true,
    ])
        ->expectsOutputToContain('The model [App\\Models\\Nobody] is not configured by an Eloquent auth provider.')
        ->doesntExpectOutputToContain('laranail/authkit-preset is ready.')
        ->assertExitCode(1);
});

it('resolves from the container', function (): void {
    expect(app(InstallCommand::class))->toBeInstanceOf(InstallCommand::class)
        ->and(app(InstallCommand::class)->getName())->toBe('laranail::authkit-preset.install');
});

it('extends the package-tools install base and keeps both console traits', function (): void {
    expect(is_subclass_of(InstallCommand::class, PackageToolsInstallCommand::class))->toBeTrue();

    $traits = class_uses(InstallCommand::class);

    expect($traits)->toHaveKey(InteractsWithConsoleServices::class)
        ->and($traits)->toHaveKey(InteractsWithConsoleWriter::class)
        ->and($traits)->toHaveKey(ReadsOptions::class);
});
