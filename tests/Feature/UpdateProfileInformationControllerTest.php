<?php

declare(strict_types=1);

uses(Tests\Concerns\UsesPublishedPresetViews::class);
beforeEach(fn () => $this->publishPresetViews());
afterEach(fn () => $this->removePublishedPresetViews());

use Workbench\App\Models\User;
use Illuminate\Support\Facades\Notification;
use Simtabi\Laranail\AuthKit\Services\EmailChange;

it(description: 'renders the profile information form for authenticated users', closure: function (): void {
    $user = User::factory()->create([
        'name'  => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);

    $this->actingAs($user)
        ->get(route('user-profile-information.edit'))
        ->assertOk()
        ->assertViewIs('laranail/authkit-preset::blade.update-profile-information')
        ->assertSee(route('dashboard'))
        ->assertSee('Ada Lovelace')
        ->assertSee('ada@example.com');
});

it(description: 'updates profile information through the web route', closure: function (): void {
    $user = User::factory()->create(['email' => 'original@example.com']);

    Notification::fake();

    $this->actingAs($user)
        ->put(uri: route('user-profile-information.update'), data: [
            'name'  => 'Grace Hopper',
            'email' => 'grace@example.com',
        ])
        ->assertRedirect();

    expect($user->fresh()->only(['name', 'email']))->toBe([
        'name'  => 'Grace Hopper',
        'email' => 'original@example.com',
    ]);
    expect(app(EmailChange::class)->pending($user)['email'])->toBe('grace@example.com');
});

it(description: 'returns profile validation errors in the Fortify error bag', closure: function (): void {
    $user = User::factory()->create(['email' => 'original@example.com']);

    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($user)
        ->from(route('user-profile-information.edit'))
        ->put(uri: route('user-profile-information.update'), data: [
            'name'  => 'Grace Hopper',
            'email' => 'taken@example.com',
        ])
        ->assertRedirect(route('user-profile-information.edit'))
        ->assertSessionHasErrorsIn(errorBag: 'updateProfileInformation', keys: ['email']);
});

it(description: 'updates profile information through the Sanctum API route', closure: function (): void {
    $user = User::factory()->create(['email' => 'original@example.com']);
    $token = $user->createToken('test-token');

    Notification::fake();

    $this->withToken($token->plainTextToken)
        ->putJson(uri: route('api.user-profile-information.update'), data: [
            'name'  => 'Grace Hopper',
            'email' => 'grace@example.com',
        ])
        ->assertOk();

    expect($user->fresh()->only(['name', 'email']))->toBe([
        'name'  => 'Grace Hopper',
        'email' => 'original@example.com',
    ]);
    expect(app(EmailChange::class)->pending($user)['email'])->toBe('grace@example.com');
});
