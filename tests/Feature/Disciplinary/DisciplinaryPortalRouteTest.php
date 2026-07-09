<?php

namespace Tests\Feature\Disciplinary;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisciplinaryPortalRouteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_disciplinary_index_redirects_abogado_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);
        $user->assignRole('abogado');

        $this->actingAs($user)
            ->get('/disciplinary')
            ->assertRedirect(route('disciplinary.dashboard'));
    }

    public function test_abogado_cases_nav_url_is_cases_index(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);
        $user->assignRole('abogado');

        $this->assertSame(route('disciplinary.cases.index'), $user->disciplinaryCasesNavUrl());
    }

    public function test_disciplinary_index_redirects_planeacion_to_coordinations(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);
        $user->assignRole('planeacion');

        $this->actingAs($user)
            ->get('/disciplinary')
            ->assertRedirect(route('disciplinary.coordinations.index'));
    }

    public function test_disciplinary_index_redirects_supervisor_to_evidences_pending(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);
        $user->assignRole('supervisor');

        $this->actingAs($user)
            ->get('/disciplinary')
            ->assertRedirect(route('disciplinary.evidences-pending.index'));
    }

    public function test_dashboard_redirects_supervisor_to_portal_instead_of_403(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);
        $user->assignRole('supervisor');

        $this->actingAs($user)
            ->get('/disciplinary/dashboard')
            ->assertRedirect(route('disciplinary.evidences-pending.index'));
    }

    public function test_cases_index_redirects_supervisor_to_portal_instead_of_403(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);
        $user->assignRole('supervisor');

        $this->actingAs($user)
            ->get('/disciplinary/cases')
            ->assertRedirect(route('disciplinary.evidences-pending.index'));
    }

    public function test_cases_index_redirects_planeacion_to_portal_instead_of_403(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);
        $user->assignRole('planeacion');

        $this->actingAs($user)
            ->get('/disciplinary/cases')
            ->assertRedirect(route('disciplinary.coordinations.index'));
    }
}
