<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\CitationArticlesIndex;
use App\Models\Disciplinary\Fault;
use App\Models\User;
use Database\Seeders\FaultsCatalogSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CitationArticlesIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(FaultsCatalogSeeder::class);
    }

    public function test_guest_cannot_access_citation_articles_settings(): void
    {
        $this->get(route('settings.citation-articles', absolute: false))
            ->assertRedirect();
    }

    public function test_non_admin_cannot_access_citation_articles_settings(): void
    {
        $user = User::factory()->create();
        $user->assignRole('nivel6');

        $this->actingAs($user)
            ->get(route('settings.citation-articles', absolute: false))
            ->assertForbidden();
    }

    public function test_admin_sees_settings_nav_tabs_on_articles_page(): void
    {
        $user = User::factory()->create();
        $user->assignRole('nivel1');

        $this->actingAs($user)
            ->get(route('settings.citation-articles', absolute: false))
            ->assertOk()
            ->assertSee('Territorio')
            ->assertSee('Artículos')
            ->assertSee('Preguntas');
    }

    public function test_admin_can_save_fault_template(): void
    {
        $user = User::factory()->create();
        $user->assignRole('nivel1');

        $fault = Fault::query()->where('code', 'F-001')->firstOrFail();

        Livewire::actingAs($user)
            ->test(CitationArticlesIndex::class)
            ->call('openManageModal', $fault->id)
            ->set('editingBlocks', [
                ['article_number' => '74', 'numerals' => '1, 2, 3'],
                ['article_number' => '76', 'numerals' => ''],
            ])
            ->call('saveFaultTemplate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('citation_statute_articles', ['number' => '74']);
        $this->assertDatabaseHas('fault_citation_templates', ['fault_id' => $fault->id]);
    }
}
