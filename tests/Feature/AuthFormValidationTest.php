<?php

declare(strict_types=1);

it('renders login field validation messages inline after an empty submission', function (): void {
    $this->from(route('login'))
        ->post(route('login.store'), [])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email', 'password']);

    $this->get(route('login'))
        ->assertSee('<p class="mt-1 text-sm text-red-600">The email field is required.</p>', escape: false)
        ->assertSee('<p class="mt-1 text-sm text-red-600">The password field is required.</p>', escape: false);
});

it('renders registration field validation messages inline after an empty submission', function (): void {
    $this->from(route('register'))
        ->post(route('register.store'), [])
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors(['name', 'email', 'password']);

    $this->get(route('register'))
        ->assertSee('<p class="mt-1 text-sm text-red-600">The name field is required.</p>', escape: false)
        ->assertSee('<p class="mt-1 text-sm text-red-600">The email field is required.</p>', escape: false)
        ->assertSee('<p class="mt-1 text-sm text-red-600">The password field is required.</p>', escape: false);
});
