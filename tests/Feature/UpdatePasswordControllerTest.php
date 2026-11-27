<?php

declare(strict_types=1);

uses(Tests\Concerns\UsesPublishedPresetViews::class);
beforeEach(fn () => $this->publishPresetViews());
afterEach(fn () => $this->removePublishedPresetViews());

use Workbench\App\Models\User;
use Illuminate\Support\Facades\Hash;
use Simtabi\Laranail\Package\Tools\Testing\TestCredentials;

it(description: 'renders the password update form for authenticated users', closure: function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('user-password.edit'))
        ->assertOk()
        ->assertViewIs('laranail/authkit-preset::blade.update-password');
});

it(description: 'updates a password through the web route', closure: function (): void {
    expect(TestCredentials::password('new'))->not->toBe(TestCredentials::password('old'));

    $user = User::factory()->create(['password' => Hash::make(TestCredentials::password('old'))]);

    $form = $this->presetForm($this->actingAs($user)->get(route('user-password.edit'))->assertOk()->getContent(), 'current_password');
    $this->{$form['method']}($form['action'], [...$form['fields'],
        'current_password'      => TestCredentials::password('old'),
        'password'              => TestCredentials::password('new'),
        'password_confirmation' => TestCredentials::password('new'),
    ])
        ->assertRedirect();

    expect(Hash::check(value: TestCredentials::password('new'), hashedValue: $user->fresh()->password))->toBeTrue();
});

it(description: 'returns password validation errors in the Fortify error bag', closure: function (): void {
    expect(TestCredentials::password('invalid'))->not->toBe(TestCredentials::password('old'));

    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('user-password.edit'))
        ->put(uri: route('user-password.update'), data: [
            'current_password'      => TestCredentials::password('invalid'),
            'password'              => TestCredentials::password('new'),
            'password_confirmation' => TestCredentials::password('new'),
        ])
        ->assertRedirect(route('user-password.edit'))
        ->assertSessionHasErrorsIn(errorBag: 'updatePassword', keys: ['current_password']);
});

// This test covers the API adapter's contract dispatch; browser form tests above use the real action.
it('delegates a Sanctum API password update to the configured Fortify action', function (): void {
    $user = User::factory()->create();
    $input = [
        'current_password'      => TestCredentials::password('old'),
        'password'              => TestCredentials::password('new'),
        'password_confirmation' => TestCredentials::password('new'),
    ];
    $updater = Mockery::mock(Laravel\Fortify\Contracts\UpdatesUserPasswords::class);
    $updater->shouldReceive('update')->once()->withArgs(fn ($actualUser, $actualInput) => $actualUser->is($user) && $actualInput === $input);
    app()->instance(Laravel\Fortify\Contracts\UpdatesUserPasswords::class, $updater);

    $this->withToken($user->createToken('test-token')->plainTextToken)
        ->putJson(route('api.user-password.update'), $input)->assertOk();
});
