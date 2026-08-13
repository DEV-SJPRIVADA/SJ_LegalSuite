<?php

namespace Tests\Feature\Disciplinary;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisciplinaryGeoJsonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guest_cannot_download_geojson(): void
    {
        $this->get(route('disciplinary.map-geo', ['file' => 'gadm41_COL_1.json'], absolute: false))
            ->assertRedirect();
    }

    public function test_admin_can_download_geojson_when_file_exists(): void
    {
        $path = public_path('geo/gadm41_COL_1.json');
        if (! is_readable($path)) {
            $this->markTestSkipped('GeoJSON no presente en public/geo; ejecute geo:download-colombia-gadm.');
        }

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);
        $user->assignRole('nivel1');

        $this->actingAs($user)
            ->get(route('disciplinary.map-geo', ['file' => 'gadm41_COL_1.json'], absolute: false))
            ->assertOk()
            ->assertHeader('content-type', 'application/geo+json; charset=utf-8');
    }
}
