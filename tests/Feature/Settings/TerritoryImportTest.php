<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\TerritoryImport;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TerritoryImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guest_is_redirected_from_settings_territory(): void
    {
        $this->get(route('settings.territory-import', absolute: false))
            ->assertRedirect();
    }

    public function test_user_without_permission_cannot_open_territory_settings(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);

        $this->actingAs($user)
            ->get(route('settings.territory-import', absolute: false))
            ->assertForbidden();
    }

    public function test_admin_can_render_territory_import_livewire(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);
        $user->assignRole('admin');

        Livewire::actingAs($user)
            ->test(TerritoryImport::class)
            ->assertOk()
            ->assertSee('Territorio');
    }
}
