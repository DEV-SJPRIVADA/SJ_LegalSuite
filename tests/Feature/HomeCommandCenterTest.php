<?php

namespace Tests\Feature;

use App\Livewire\Home;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomeCommandCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_view_home_command_center(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeLivewire(Home::class)
            ->assertSee('Command center')
            ->assertSee('Casos por etapa del flujo')
            ->assertSee('Casos por ciudad')
            ->assertSee('Top municipios');
    }

    public function test_abogado_is_redirected_from_dashboard(): void
    {
        $lawyer = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);
        $lawyer->assignRole('abogado');

        $this->actingAs($lawyer)
            ->get('/dashboard')
            ->assertRedirect($lawyer->suiteLandingUrl());
    }

    public function test_supervisor_is_redirected_from_dashboard(): void
    {
        $supervisor = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);
        $supervisor->assignRole('supervisor');

        $this->actingAs($supervisor)
            ->get('/dashboard')
            ->assertRedirect(route('disciplinary.evidences-pending.index'));
    }

    public function test_admin_sidebar_includes_inicio_link(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(Home::class)
            ->assertSee('Dirección jurídica');
    }

    public function test_abogado_suite_landing_is_disciplinary_dashboard(): void
    {
        $lawyer = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);
        $lawyer->assignRole('abogado');

        $this->assertSame(route('disciplinary.dashboard'), $lawyer->suiteLandingUrl());
    }
}
