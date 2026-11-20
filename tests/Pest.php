<?php

declare(strict_types=1);
use Tests\TestCase;

uses(TestCase::class, Illuminate\Foundation\Testing\LazilyRefreshDatabase::class)->in('Feature', 'Unit', 'Architecture');
