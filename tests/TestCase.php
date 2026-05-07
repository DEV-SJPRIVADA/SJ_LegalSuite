<?php

namespace Tests;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->usesRefreshDatabase()) {
            $this->seed(RolesAndPermissionsSeeder::class);
        }
    }

    protected function usesRefreshDatabase(): bool
    {
        foreach (class_uses_recursive(static::class) as $trait) {
            if ($trait === RefreshDatabase::class) {
                return true;
            }
        }

        return false;
    }
}
