<?php

namespace Tests\Feature\Disciplinary;

use App\Models\ColombianMunicipality;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoGj51PreparerSignatureTest extends TestCase
{
    use RefreshDatabase;

    private function makeEmployee(): Employee
    {
        return Employee::query()->create([
            'first_name' => 'Trabajador',
            'last_name' => 'Prueba',
            'document_number' => '9500'.random_int(100000, 999999),
        ]);
    }

    public function test_pdf_action_requires_captured_preparer_signature(): void
    {
        $supervisor = $this->makeSupervisor();
        $employee = $this->makeEmployee();
        $this->seedMunicipality();

        $response = $this->actingAs($supervisor)->post(route('disciplinary.forms.informe.process'), [
            'fo51_action' => 'pdf',
            'fo51_worker_name' => $employee->full_name,
            'fo51_worker_document' => $employee->document_number,
            'fo51_employee_id' => $employee->id,
            'fo51_municipality_code' => '76001',
            'fo51_observations' => 'Hechos de prueba.',
        ]);

        $response->assertSessionHasErrors('fo51_preparer_signature');
    }

    public function test_pdf_action_rejects_text_instead_of_signature_image(): void
    {
        $supervisor = $this->makeSupervisor();
        $employee = $this->makeEmployee();
        $this->seedMunicipality();

        $response = $this->actingAs($supervisor)->post(route('disciplinary.forms.informe.process'), [
            'fo51_action' => 'pdf',
            'fo51_worker_name' => $employee->full_name,
            'fo51_worker_document' => $employee->document_number,
            'fo51_employee_id' => $employee->id,
            'fo51_municipality_code' => '76001',
            'fo51_observations' => 'Hechos de prueba.',
            'fo51_preparer_signature' => 'firma escrita a mano',
        ]);

        $response->assertSessionHasErrors('fo51_preparer_signature');
    }

    public function test_supervisor_validation_errors_redirect_to_full_page_form_not_cases_index(): void
    {
        $supervisor = $this->makeSupervisor();

        $response = $this->actingAs($supervisor)->post(route('disciplinary.forms.informe.process'), [
            'fo51_action' => 'enviar',
        ]);

        $response->assertRedirect(route('disciplinary.forms.informe-fo-gj-51', ['vista_completa' => 1]));
        $response->assertSessionHasErrors();
    }

    public function test_filled_pdf_view_renders_preparer_signature_image(): void
    {
        $signature = $this->sampleSignatureDataUri();

        $html = view('disciplinary.forms.fo-gj-51-filled-download', [
            'embeddedLogoSrc' => 'data:image/png;base64,AA==',
            'workerName' => 'Trabajador Prueba',
            'workerDocument' => '1234567890',
            'workerCargo' => 'Operario',
            'city' => 'Cali',
            'shift' => 'Mañana',
            'position' => 'Puesto 1',
            'faultOtherDetail' => '',
            'observations' => 'Observaciones.',
            'preparerName' => 'Supervisor campo',
            'preparerRole' => 'supervisor',
            'preparerSignature' => $signature,
            'reportDay' => '16',
            'reportMonth' => '06',
            'reportYear' => '2026',
            'faultLeftChecked' => [],
            'faultRightChecked' => [],
            'faultOtherChecked' => false,
            'jurPd' => '',
            'entregaGh' => '',
            'jurDd' => '',
            'jurMm' => '',
            'jurYyyy' => '',
        ])->render();

        $this->assertStringContainsString('class="fo51-signature-img"', $html);
        $this->assertStringContainsString($signature, $html);
    }

    public function test_informe_form_includes_signature_capture_alpine_component(): void
    {
        $supervisor = $this->makeSupervisor();

        $response = $this->actingAs($supervisor)->get(route('disciplinary.forms.informe-fo-gj-51', [
            'vista_completa' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('fo51-interactive', false);
        $response->assertSee('fo51-block-personal', false);
        $response->assertSee('fo51-personal-cell', false);
        $response->assertSee('fo51-inline-lbl', false);
        $response->assertSee('sjFo51PreparerSignature', false);
        $response->assertSee('Capturar firma', false);
        $response->assertSee('Agregar evidencias (opcional)', false);
        $response->assertSee('form="fo51-informe-form"', false);
        $response->assertDontSee('x-model="evidenceModalOpen"', false);
        $response->assertDontSee('name="fo51_preparer_signature" class="fo51-in"', false);
    }

    public function test_filled_pdf_view_does_not_include_mobile_interactive_layout(): void
    {
        $signature = $this->sampleSignatureDataUri();

        $html = view('disciplinary.forms.fo-gj-51-filled-download', [
            'embeddedLogoSrc' => 'data:image/png;base64,AA==',
            'workerName' => 'Trabajador Prueba',
            'workerDocument' => '1234567890',
            'workerCargo' => 'Operario',
            'city' => 'Cali',
            'shift' => 'Mañana',
            'position' => 'Puesto 1',
            'faultOtherDetail' => '',
            'observations' => 'Observaciones.',
            'preparerName' => 'Supervisor campo',
            'preparerRole' => 'supervisor',
            'preparerSignature' => $signature,
            'reportDay' => '16',
            'reportMonth' => '06',
            'reportYear' => '2026',
            'faultLeftChecked' => [],
            'faultRightChecked' => [],
            'faultOtherChecked' => false,
            'jurPd' => '',
            'entregaGh' => '',
            'jurDd' => '',
            'jurMm' => '',
            'jurYyyy' => '',
        ])->render();

        $this->assertStringNotContainsString('fo51-interactive', $html);
        $this->assertStringNotContainsString('@media (max-width: 767px)', $html);
    }

    private function makeSupervisor(): User
    {
        $user = User::factory()->create([
            'email' => 'supervisor-fo51-'.random_int(1000, 9999).'@test.local',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'is_active' => true,
            'position' => 'supervisor',
        ]);
        $user->assignRole('supervisor');

        return $user;
    }

    private function seedMunicipality(): void
    {
        ColombianMunicipality::query()->create([
            'department_code' => '76',
            'department_name' => 'Valle del Cauca',
            'municipality_code' => '76001',
            'municipality_name' => 'Cali',
        ]);
    }

    private function sampleSignatureDataUri(): string
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        );

        return 'data:image/png;base64,'.base64_encode($png ?: '');
    }
}
