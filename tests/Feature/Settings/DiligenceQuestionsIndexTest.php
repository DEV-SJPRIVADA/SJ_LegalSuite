<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\DiligenceQuestionsIndex;
use App\Models\Disciplinary\DiligenceActaQuestion;
use App\Models\User;
use App\Services\Settings\DiligenceActaQuestionCatalogService;
use Database\Seeders\DiligenceActaQuestionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DiligenceQuestionsIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_guest_cannot_access_diligence_questions_settings(): void
    {
        $this->get(route('settings.diligence-questions', absolute: false))
            ->assertRedirect();
    }

    public function test_non_admin_cannot_access_diligence_questions_settings(): void
    {
        $user = User::factory()->create();
        $user->assignRole('nivel6');

        $this->actingAs($user)
            ->get(route('settings.diligence-questions', absolute: false))
            ->assertForbidden();
    }

    public function test_admin_sees_questions_tab_and_can_crud(): void
    {
        $user = User::factory()->create();
        $user->assignRole('nivel1');

        $this->actingAs($user)
            ->get(route('settings.diligence-questions', absolute: false))
            ->assertOk()
            ->assertSee('Preguntas')
            ->assertSee('Preguntas de diligencia');

        Livewire::actingAs($user)
            ->test(DiligenceQuestionsIndex::class)
            ->call('openCreateModal')
            ->set('questionText', 'Reconoce los hechos de la citación')
            ->call('saveQuestion')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('diligence_acta_questions', [
            'text' => 'Reconoce los hechos de la citación',
        ]);

        $question = DiligenceActaQuestion::query()->firstOrFail();

        Livewire::actingAs($user)
            ->test(DiligenceQuestionsIndex::class)
            ->call('openEditModal', $question->id)
            ->set('questionText', 'Reconoce los hechos descritos')
            ->call('saveQuestion')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('diligence_acta_questions', [
            'id' => $question->id,
            'text' => 'Reconoce los hechos descritos',
        ]);

        Livewire::actingAs($user)
            ->test(DiligenceQuestionsIndex::class)
            ->call('deleteQuestion', $question->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('diligence_acta_questions', ['id' => $question->id]);
    }

    public function test_seeder_creates_default_questions(): void
    {
        $this->seed(DiligenceActaQuestionsSeeder::class);
        $this->assertGreaterThanOrEqual(1, DiligenceActaQuestion::query()->count());

        $catalog = app(DiligenceActaQuestionCatalogService::class);
        $first = $catalog->listOrdered()->first();
        $second = $catalog->listOrdered()->skip(1)->first();
        $this->assertNotNull($first);
        $this->assertNotNull($second);

        $catalog->moveDown($first->fresh());
        $this->assertSame($second->id, $catalog->listOrdered()->first()->id);
    }
}
