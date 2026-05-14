<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApplicationEncryptionConfigTest extends TestCase
{
    public function test_application_encryption_key_is_configured(): void
    {
        $key = config('app.key');

        $this->assertNotEmpty($key, 'APP_KEY debe estar definido (.env o phpunit.xml) para sesión, CSRF y Livewire.');
        $this->assertStringStartsWith('base64:', $key);
    }
}
