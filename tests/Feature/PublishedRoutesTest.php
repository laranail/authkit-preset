<?php

declare(strict_types=1);

use Workbench\App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Contracts\Console\Kernel;

trait PublishedPresetRoutesEnvironment
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('laranail.authkit-preset.routes.mode', 'published');
    }

    protected function defineRoutes($router): void
    {
        foreach (['laranail-authkit-preset-web.php', 'laranail-authkit-preset-web-mount.php'] as $file) {
            File::delete(base_path('routes/' . $file));
        }
        $status = app(Kernel::class)->call('vendor:publish', ['--tag' => 'laranail::authkit-preset-routes']);
        expect($status)->toBe(0);
        require base_path('routes/laranail-authkit-preset-web.php');
    }
}

uses(PublishedPresetRoutesEnvironment::class, Tests\Concerns\UsesPublishedPresetViews::class);
beforeEach(fn () => $this->publishPresetViews());
afterEach(function (): void {
    $this->removePublishedPresetViews();
    File::delete([base_path('routes/laranail-authkit-preset-web.php'), base_path('routes/laranail-authkit-preset-web-mount.php')]);
});

it('loads the published route entry and its mount file and serves the published forms', function (): void {
    $this->get(route('login'))->assertOk()->assertSee('name="email"', false);
    $this->actingAs(User::factory()->create())->get(route('user-profile-information.edit'))->assertOk();
    expect(app('router')->has('laranail-auth.user-email-change.confirm'))->toBeTrue();
});
