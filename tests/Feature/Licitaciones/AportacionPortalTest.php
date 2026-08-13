<?php

namespace Tests\Feature\Licitaciones;

use App\Enums\Licitaciones\DocumentRevisionStatus;
use App\Enums\Licitaciones\PetitionType;
use App\Enums\Licitaciones\RequestStatus;
use App\Enums\Licitaciones\RequestType;
use App\Models\Licitaciones\Licitacion;
use App\Models\Licitaciones\LicitacionSolicitud;
use App\Models\Licitaciones\LicitacionSolicitudInvitado;
use App\Models\User;
use App\Notifications\Licitaciones\DocumentoAportadoNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AportacionPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_aportante_can_upload_document_and_staff_sees_it(): void
    {
        Storage::fake('local');
        Notification::fake();

        $creator = User::factory()->create([
            'email' => 'soporte.admin@sjsp.com.co',
        ]);

        $licitacion = Licitacion::create([
            'responsable_principal_id' => $creator->id,
            'entidad_contratante' => 'COmeva',
            'numero_proceso' => '1523',
            'objeto' => 'Prueba',
            'estado_proceso' => 'avanzando',
        ]);

        $solicitud = LicitacionSolicitud::create([
            'licitacion_id' => $licitacion->id,
            'numero_radicado' => 'RAD-TEST-1',
            'fecha_creacion' => now()->toDateString(),
            'nombre' => 'Solicitud prueba',
            'descripcion' => 'HV',
            'area_responsable' => 'GH',
            'usuario_responsable_id' => $creator->id,
            'tipo_solicitud' => RequestType::Esporadica->value,
            'tipo_peticion' => PetitionType::Documentacion->value,
            'fecha_limite' => now()->addDays(3)->toDateString(),
            'estado' => RequestStatus::Recibido->value,
            'created_by_id' => $creator->id,
            'email_notificacion' => 'soporte.admin@sjsp.com.co',
        ]);

        $invitado = LicitacionSolicitudInvitado::create([
            'solicitud_id' => $solicitud->id,
            'email' => 'aportante@example.com',
            'token' => LicitacionSolicitudInvitado::generateToken(),
            'invitado_at' => now(),
            'invitado_por_id' => $creator->id,
        ]);

        $response = $this->post(route('licitaciones.aportacion.store', $invitado->token), [
            'archivo' => UploadedFile::fake()->create('hv.pdf', 120, 'application/pdf'),
        ]);

        $response->assertRedirect(route('licitaciones.aportacion', $invitado->token));
        $response->assertSessionHas('aportacion_enviada', true);

        $this->assertDatabaseHas('licitacion_adjuntos', [
            'solicitud_id' => $solicitud->id,
            'invitado_id' => $invitado->id,
            'nombre_archivo' => 'hv.pdf',
            'revision_estado' => DocumentRevisionStatus::Pendiente->value,
            'uploader_email' => 'aportante@example.com',
        ]);

        $this->get(route('licitaciones.aportacion', $invitado->token))
            ->assertOk()
            ->assertSee('¡Documento enviado!')
            ->assertSee('hv.pdf');

        Notification::assertSentTo($creator, DocumentoAportadoNotification::class);
    }
}
