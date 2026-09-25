<?php

namespace Tests\Feature\Licitaciones;

use App\Enums\Licitaciones\PetitionType;
use App\Enums\Licitaciones\RequestStatus;
use App\Enums\Licitaciones\RequestType;
use App\Jobs\Licitaciones\SendLicitacionAportacionRecordatorioJob;
use App\Models\Licitaciones\Licitacion;
use App\Models\Licitaciones\LicitacionSolicitud;
use App\Models\Licitaciones\LicitacionSolicitudInvitado;
use App\Models\User;
use App\Notifications\Licitaciones\SolicitudDocumentacionRecordatorioNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AportacionRecordatorioTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_sends_reminder_when_documents_are_pending_near_deadline(): void
    {
        Notification::fake();

        $creator = User::factory()->create();
        $licitacion = Licitacion::create([
            'responsable_principal_id' => $creator->id,
            'entidad_contratante' => 'Alcaldía',
            'numero_proceso' => 'P-1',
            'objeto' => 'Prueba',
            'estado_proceso' => 'avanzando',
        ]);

        $solicitud = LicitacionSolicitud::create([
            'licitacion_id' => $licitacion->id,
            'numero_radicado' => 'RAD-REC-1',
            'fecha_creacion' => now()->toDateString(),
            'nombre' => 'Solicitud recordatorio',
            'area_responsable' => 'Jurídica',
            'usuario_responsable_id' => $creator->id,
            'tipo_solicitud' => RequestType::Esporadica->value,
            'tipo_peticion' => PetitionType::Documentacion->value,
            'fecha_limite' => now()->addDay()->toDateString(),
            'aportacion_limite_at' => now()->addHours(12),
            'estado' => RequestStatus::Recibido->value,
            'created_by_id' => $creator->id,
        ]);

        $invitado = LicitacionSolicitudInvitado::create([
            'solicitud_id' => $solicitud->id,
            'email' => 'aportante@example.com',
            'token' => LicitacionSolicitudInvitado::generateToken(),
            'invitado_at' => now()->subDay(),
            'notificado_at' => now()->subDay(),
            'invitado_por_id' => $creator->id,
        ]);

        $this->artisan('licitaciones:enviar-recordatorios-aportacion')
            ->assertSuccessful();

        Notification::assertSentOnDemand(SolicitudDocumentacionRecordatorioNotification::class);
        $this->assertNotNull($invitado->fresh()->ultimo_recordatorio_at);
    }

    public function test_final_hour_reminder_runs_every_ten_minutes_until_upload(): void
    {
        Notification::fake();

        $creator = User::factory()->create();
        $licitacion = Licitacion::create([
            'responsable_principal_id' => $creator->id,
            'entidad_contratante' => 'Alcaldía',
            'numero_proceso' => 'P-3',
            'objeto' => 'Prueba última hora',
            'estado_proceso' => 'avanzando',
        ]);

        $solicitud = LicitacionSolicitud::create([
            'licitacion_id' => $licitacion->id,
            'numero_radicado' => 'RAD-REC-3',
            'fecha_creacion' => now()->toDateString(),
            'nombre' => 'Solicitud última hora',
            'area_responsable' => 'Jurídica',
            'usuario_responsable_id' => $creator->id,
            'tipo_solicitud' => RequestType::Esporadica->value,
            'tipo_peticion' => PetitionType::Documentacion->value,
            'fecha_limite' => now()->toDateString(),
            'aportacion_limite_at' => now()->addMinutes(45),
            'estado' => RequestStatus::Recibido->value,
            'created_by_id' => $creator->id,
        ]);

        $invitado = LicitacionSolicitudInvitado::create([
            'solicitud_id' => $solicitud->id,
            'email' => 'urgente@example.com',
            'token' => LicitacionSolicitudInvitado::generateToken(),
            'invitado_at' => now()->subDay(),
            'notificado_at' => now()->subDay(),
            'ultimo_recordatorio_at' => now()->subMinutes(11),
            'invitado_por_id' => $creator->id,
        ]);

        $this->assertTrue($invitado->fresh()->load('solicitud')->isInFinalHourWindow());
        $this->assertTrue($invitado->fresh()->load('solicitud')->needsAportacionReminder());

        $this->artisan('licitaciones:enviar-recordatorios-aportacion')
            ->assertSuccessful();

        Notification::assertSentOnDemand(SolicitudDocumentacionRecordatorioNotification::class);

        $invitado->refresh();
        $invitado->update(['ultimo_recordatorio_at' => now()->subMinutes(5)]);
        $this->assertFalse($invitado->fresh()->load('solicitud')->needsAportacionReminder());
    }

    public function test_reminder_job_skips_when_aportante_already_uploaded(): void
    {
        Notification::fake();

        $creator = User::factory()->create();
        $licitacion = Licitacion::create([
            'responsable_principal_id' => $creator->id,
            'entidad_contratante' => 'Alcaldía',
            'numero_proceso' => 'P-2',
            'objeto' => 'Prueba',
            'estado_proceso' => 'avanzando',
        ]);

        $solicitud = LicitacionSolicitud::create([
            'licitacion_id' => $licitacion->id,
            'numero_radicado' => 'RAD-REC-2',
            'fecha_creacion' => now()->toDateString(),
            'nombre' => 'Solicitud con archivo',
            'area_responsable' => 'Jurídica',
            'usuario_responsable_id' => $creator->id,
            'tipo_solicitud' => RequestType::Esporadica->value,
            'tipo_peticion' => PetitionType::Documentacion->value,
            'fecha_limite' => now()->addDay()->toDateString(),
            'aportacion_limite_at' => now()->addHours(6),
            'estado' => RequestStatus::Recibido->value,
            'created_by_id' => $creator->id,
        ]);

        $invitado = LicitacionSolicitudInvitado::create([
            'solicitud_id' => $solicitud->id,
            'email' => 'aportante2@example.com',
            'token' => LicitacionSolicitudInvitado::generateToken(),
            'invitado_at' => now()->subDay(),
            'notificado_at' => now()->subDay(),
            'invitado_por_id' => $creator->id,
        ]);

        $invitado->adjuntos()->create([
            'solicitud_id' => $solicitud->id,
            'licitacion_id' => $licitacion->id,
            'nombre_archivo' => 'doc.pdf',
            'disk' => 'local',
            'path' => 'licitaciones/test.pdf',
            'revision_estado' => 'pendiente',
            'uploader_email' => 'aportante2@example.com',
        ]);

        SendLicitacionAportacionRecordatorioJob::dispatchSync($invitado->id);

        Notification::assertNothingSent();
        $this->assertNull($invitado->fresh()->ultimo_recordatorio_at);
    }
}
