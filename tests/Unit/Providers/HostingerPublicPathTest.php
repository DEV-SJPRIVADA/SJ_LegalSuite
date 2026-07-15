<?php

namespace Tests\Unit\Providers;

use Tests\TestCase;

class HostingerPublicPathTest extends TestCase
{
    public function test_public_path_uses_public_html_when_present(): void
    {
        $hostingerRoot = base_path('public_html');
        if (! is_dir($hostingerRoot)) {
            $this->markTestSkipped('Sin public_html (entorno Laragon local).');
        }

        $this->assertSame(
            realpath($hostingerRoot),
            realpath(public_path()),
            'En Hostinger public_path() debe apuntar a public_html.',
        );
    }

    public function test_local_public_path_stays_public_when_no_public_html(): void
    {
        if (is_dir(base_path('public_html'))) {
            $this->markTestSkipped('Hay public_html (entorno Hostinger).');
        }

        $this->assertSame(
            realpath(base_path('public')),
            realpath(public_path()),
        );
    }
}
