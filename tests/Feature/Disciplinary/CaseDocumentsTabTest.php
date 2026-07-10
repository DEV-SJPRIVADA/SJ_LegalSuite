<?php

namespace Tests\Feature\Disciplinary;

use App\Enums\Disciplinary\CaseStatus;
use App\Enums\Disciplinary\DocumentType;
use App\Livewire\Disciplinary\Cases\CaseDetail;
use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\Disciplinary\DisciplinaryDocument;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CaseDocumentsTabTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('local');
    }

    public function test_documents_tab_shows_cards_with_preview_and_download_actions(): void
    {
        ['case' => $case, 'lawyer' => $lawyer, 'pdf' => $pdf] = $this->makeCaseWithDocuments();

        Livewire::actingAs($lawyer)
            ->test(CaseDetail::class, ['case' => $case])
            ->set('activeTab', 'documents')
            ->assertSee('FO-GJ-03-citacion-test.pdf')
            ->assertSee('Imagen.png')
            ->assertDontSee('evidencia.png')
            ->assertSee('Previsualizar')
            ->assertSee('Descargar')
            ->assertSee('FO-GJ-03')
            ->call('openDocumentPreview', $pdf->id)
            ->assertSet('documentPreviewId', $pdf->id);
    }

    public function test_case_document_can_be_served_inline_and_as_download(): void
    {
        ['case' => $case, 'lawyer' => $lawyer, 'pdf' => $pdf] = $this->makeCaseWithDocuments();

        $inline = $this->actingAs($lawyer)
            ->get(route('disciplinary.cases.documents.file', ['case' => $case, 'document' => $pdf]));

        $inline->assertOk();
        $this->assertStringContainsString('inline', (string) $inline->headers->get('Content-Disposition'));

        $download = $this->actingAs($lawyer)
            ->get(route('disciplinary.cases.documents.file', ['case' => $case, 'document' => $pdf, 'download' => 1]));

        $download->assertOk();
        $this->assertStringContainsString('attachment', (string) $download->headers->get('Content-Disposition'));
    }

    /** @return array{case: DisciplinaryCase, lawyer: User, pdf: DisciplinaryDocument} */
    private function makeCaseWithDocuments(): array
    {
        $lawyer = User::factory()->create([
            'email' => 'docs-lawyer-'.random_int(1000, 9999).'@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
        ]);
        $lawyer->assignRole('nivel6');

        $employee = Employee::query()->create([
            'first_name' => 'Worker',
            'last_name' => 'Docs',
            'document_number' => '9200'.random_int(100000, 999999),
        ]);

        $case = DisciplinaryCase::query()->create([
            'case_number' => 'GJ-PD:000088',
            'employee_id' => $employee->id,
            'assigned_lawyer_id' => $lawyer->id,
            'current_status' => CaseStatus::CITACION_PROGRAMADA,
            'opened_at' => now()->toDateString(),
        ]);

        Storage::disk('local')->put('disciplinary/'.$case->id.'/fo03.pdf', '%PDF-1.4 test');
        $pdf = DisciplinaryDocument::query()->create([
            'disciplinary_case_id' => $case->id,
            'uploaded_by' => $lawyer->id,
            'document_type' => DocumentType::CITACION,
            'form_code' => 'FO-GJ-03',
            'original_name' => 'FO-GJ-03-citacion-test.pdf',
            'disk' => 'local',
            'path' => 'disciplinary/'.$case->id.'/fo03.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1200,
        ]);

        Storage::disk('local')->put('disciplinary/'.$case->id.'/evidence.png', 'png-binary');
        DisciplinaryDocument::query()->create([
            'disciplinary_case_id' => $case->id,
            'uploaded_by' => $lawyer->id,
            'document_type' => DocumentType::EVIDENCIA,
            'original_name' => 'evidencia.png',
            'disk' => 'local',
            'path' => 'disciplinary/'.$case->id.'/evidence.png',
            'mime_type' => 'image/png',
            'size_bytes' => 800,
        ]);

        return ['case' => $case->fresh(['documents.uploader']), 'lawyer' => $lawyer, 'pdf' => $pdf];
    }
}
