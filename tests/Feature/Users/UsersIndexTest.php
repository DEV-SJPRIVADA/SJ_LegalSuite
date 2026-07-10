<?php

namespace Tests\Feature\Users;

use App\Enums\PlatformLevel;
use App\Livewire\Users\UsersIndex;
use App\Models\JobPosition;
use App\Models\OrganizationalArea;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UsersIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_open_edit_loads_active_and_read_only_flags(): void
    {
        $admin = $this->adminUser();
        $target = User::factory()->create([
            'email' => 'readonly-'.random_int(1000, 9999).'@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => false,
            'read_only' => true,
        ]);
        $target->assignRole(PlatformLevel::Nivel6->value);

        Livewire::actingAs($admin)
            ->test(UsersIndex::class)
            ->call('openEdit', $target->id)
            ->assertSet('isActive', false)
            ->assertSet('allowChanges', false);
    }

    public function test_kpi_filter_solo_lectura(): void
    {
        $admin = $this->adminUser();

        User::factory()->create([
            'email' => 'writable-'.random_int(1000, 9999).'@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
            'read_only' => false,
            'name' => 'Usuario Escritura',
        ])->assignRole(PlatformLevel::Nivel6->value);

        User::factory()->create([
            'email' => 'readonly-kpi-'.random_int(1000, 9999).'@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
            'read_only' => true,
            'name' => 'Usuario Solo Lectura',
        ])->assignRole(PlatformLevel::Nivel6->value);

        Livewire::actingAs($admin)
            ->test(UsersIndex::class)
            ->call('applyKpiFilter', 'solo_lectura')
            ->assertSet('accessFilter', 'solo_lectura')
            ->assertSee('Usuario Solo Lectura')
            ->assertDontSee('Usuario Escritura');
    }

    public function test_kpi_filter_admins(): void
    {
        $admin = $this->adminUser();

        Livewire::actingAs($admin)
            ->test(UsersIndex::class)
            ->call('applyKpiFilter', 'admins')
            ->assertSet('role', PlatformLevel::Nivel1->value)
            ->assertSee($admin->name);
    }

    public function test_user_initials_and_cargo_label(): void
    {
        $area = OrganizationalArea::query()->where('is_active', true)->firstOrFail();
        $position = JobPosition::query()
            ->where('organizational_area_id', $area->id)
            ->where('is_active', true)
            ->firstOrFail();

        $user = User::factory()->create([
            'name' => 'Ana Paula Gómez',
            'email' => 'ana-'.random_int(1000, 9999).'@test.local',
            'organizational_area_id' => $area->id,
            'job_position_id' => $position->id,
        ]);

        $this->assertSame('AP', $user->initials());
        $this->assertSame($position->name, $user->cargoDisplayLabel());
    }

    private function adminUser(): User
    {
        $admin = User::factory()->create([
            'email' => 'admin-users-'.random_int(1000, 9999).'@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $admin->assignRole('nivel1');

        return $admin;
    }
}
