<?php

namespace Database\Seeders;

use App\Enums\Licitaciones\PetitionType;
use App\Enums\Licitaciones\RequestStatus;
use App\Enums\Licitaciones\RequestType;
use App\Enums\PlatformLevel;
use App\Models\Licitaciones\Licitacion;
use App\Models\Licitaciones\LicitacionSolicitud;
use App\Models\Licitaciones\LicitacionSolicitudInvitado;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Datos de ejemplo de licitaciones/solicitudes para demos (local u Hostinger).
 * Idempotente por numero_proceso / numero_radicado.
 */
class DemoLicitacionesSeeder extends Seeder
{
    public function run(): void
    {
        $actor = $this->resolveActor();
        if (! $actor) {
            $this->command?->error('No hay usuarios activos para asignar como responsable. Cree un usuario primero.');

            return;
        }

        $path = database_path('data/demo_licitaciones.json');
        $payload = File::exists($path)
            ? json_decode(File::get($path), true)
            : null;

        $licitaciones = is_array($payload['licitaciones'] ?? null) ? $payload['licitaciones'] : $this->fallbackLicitaciones();
        $solicitudes = is_array($payload['solicitudes'] ?? null) ? $payload['solicitudes'] : $this->fallbackSolicitudes();

        $procesoIds = [];
        foreach ($licitaciones as $row) {
            $numero = trim((string) ($row['numero_proceso'] ?? ''));
            if ($numero === '') {
                continue;
            }

            $lic = Licitacion::query()->updateOrCreate(
                ['numero_proceso' => $numero],
                [
                    'responsable_principal_id' => $actor->id,
                    'entidad_contratante' => $row['entidad_contratante'] ?? 'Entidad demo',
                    'modalidad_contratacion' => $row['modalidad_contratacion'] ?? 'Selección abreviada',
                    'objeto' => $row['objeto'] ?? 'Objeto demo',
                    'cuantia' => $row['cuantia'] ?? null,
                    'plazo_ejecucion' => $row['plazo_ejecucion'] ?? null,
                    'lugar_ejecucion' => $row['lugar_ejecucion'] ?? 'Colombia',
                    'medio_presentacion' => $row['medio_presentacion'] ?? 'SECOP II',
                    'enlace_proceso' => $row['enlace_proceso'] ?: null,
                    'participacion_tipo' => $row['participacion_tipo'] ?? null,
                    'integrantes_participacion' => $row['integrantes_participacion'] ?? null,
                    'fecha_cierre_oferta' => $this->toDate($row['fecha_cierre_oferta'] ?? null) ?? now()->addDays(20)->toDateString(),
                    'hora_cierre_oferta' => $row['hora_cierre_oferta'] ?? '17:00:00',
                    'fecha_observaciones_evaluacion' => $this->toDate($row['fecha_observaciones_evaluacion'] ?? null),
                    'fecha_adjudicacion' => $this->toDate($row['fecha_adjudicacion'] ?? null),
                    'cumplimos' => $row['cumplimos'] ?: null,
                    'motivo_no_cumplir' => $row['motivo_no_cumplir'] ?: null,
                    'estado_proceso' => $row['estado_proceso'] ?? 'avanzando',
                    'resultado' => $row['resultado'] ?: null,
                    'adjudicado' => $row['adjudicado'] ?: null,
                    'motivo_perdida' => $row['motivo_perdida'] ?: null,
                ]
            );
            $procesoIds[$numero] = $lic->id;
        }

        $createdSolicitudes = 0;
        foreach ($solicitudes as $i => $row) {
            $radicado = trim((string) ($row['numero_radicado'] ?? ''));
            if ($radicado === '') {
                continue;
            }

            $proceso = trim((string) ($row['numero_proceso'] ?? ''));
            $licId = $procesoIds[$proceso] ?? null;

            // Fechas relativas para que el dashboard muestre vencimientos próximos.
            $fechaLimite = match ($i % 3) {
                0 => now()->addDays(3)->toDateString(),
                1 => now()->addDays(10)->toDateString(),
                default => now()->subDays(2)->toDateString(),
            };
            $aportacionLimite = match ($i % 3) {
                0 => now()->addHours(20),
                1 => now()->addDays(5)->setTime(17, 0),
                default => now()->subDay()->setTime(17, 0),
            };

            $sol = LicitacionSolicitud::query()->updateOrCreate(
                ['numero_radicado' => $radicado],
                [
                    'licitacion_id' => $licId,
                    'fecha_creacion' => $row['fecha_creacion'] ?? now()->toDateString(),
                    'nombre' => $row['nombre'] ?? 'Solicitud demo',
                    'descripcion' => $row['descripcion'] ?? 'Documentación de ejemplo para demostración.',
                    'area_responsable' => $row['area_responsable'] ?? 'Jurídica',
                    'usuario_responsable_id' => $actor->id,
                    'tipo_solicitud' => $row['tipo_solicitud'] ?? RequestType::Esporadica->value,
                    'periodicidad' => $row['periodicidad'] ?? null,
                    'tipo_peticion' => $row['tipo_peticion'] ?? PetitionType::Documentacion->value,
                    'fecha_limite' => $fechaLimite,
                    'aportacion_limite_at' => $aportacionLimite,
                    'estado' => $row['estado'] ?? RequestStatus::EnTramite->value,
                    'created_by_id' => $actor->id,
                    'email_notificacion' => $row['email_notificacion'] ?? $actor->email,
                ]
            );
            $createdSolicitudes++;

            // Invitado demo pendiente (para lista de última hora / recordatorios).
            if ($i === 0) {
                LicitacionSolicitudInvitado::query()->updateOrCreate(
                    [
                        'solicitud_id' => $sol->id,
                        'email' => 'aportante.demo@example.com',
                    ],
                    [
                        'nombre' => 'Aportante Demo',
                        'token' => LicitacionSolicitudInvitado::generateToken(),
                        'mensaje' => 'Favor cargar la documentación de ejemplo.',
                        'invitado_at' => now()->subDay(),
                        'notificado_at' => now()->subDay(),
                        'invitado_por_id' => $actor->id,
                    ]
                );
            }
        }

        // Extra: solicitud suelta con vencimiento mañana para enriquecer el gráfico.
        LicitacionSolicitud::query()->updateOrCreate(
            ['numero_radicado' => 'DEMO-VENC-PROX'],
            [
                'licitacion_id' => $procesoIds[array_key_first($procesoIds)] ?? null,
                'fecha_creacion' => now()->toDateString(),
                'nombre' => 'Solicitud demo vencimiento próximo',
                'descripcion' => 'Creada por DemoLicitacionesSeeder para el dashboard.',
                'area_responsable' => 'Jurídica',
                'usuario_responsable_id' => $actor->id,
                'tipo_solicitud' => RequestType::Esporadica->value,
                'tipo_peticion' => PetitionType::Documentacion->value,
                'fecha_limite' => now()->addDay()->toDateString(),
                'aportacion_limite_at' => now()->addDay()->setTime(12, 0),
                'estado' => RequestStatus::Recibido->value,
                'created_by_id' => $actor->id,
                'email_notificacion' => $actor->email,
            ]
        );

        $this->command?->info(sprintf(
            'Demo licitaciones: %d procesos, %d solicitudes (responsable: %s).',
            count($procesoIds),
            $createdSolicitudes + 1,
            $actor->email
        ));
    }

    private function resolveActor(): ?User
    {
        $preferredEmails = [
            'admin@sjlegalsuite.local',
            'abogado@sjlegalsuite.local',
        ];

        foreach ($preferredEmails as $email) {
            $user = User::query()->active()->where('email', $email)->first();
            if ($user) {
                return $user;
            }
        }

        $nivel1 = User::query()->active()->get()->first(fn (User $u) => $u->hasPlatformLevel(PlatformLevel::Nivel1));
        if ($nivel1) {
            return $nivel1;
        }

        $nivel6 = User::query()->active()->get()->first(fn (User $u) => $u->hasPlatformLevel(PlatformLevel::Nivel6));
        if ($nivel6) {
            return $nivel6;
        }

        return User::query()->active()->orderBy('id')->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fallbackLicitaciones(): array
    {
        return [
            [
                'numero_proceso' => '1523',
                'entidad_contratante' => 'Comeva',
                'objeto' => 'Servicio de vigilancia demo',
                'estado_proceso' => 'avanzando',
                'modalidad_contratacion' => 'Licitación pública',
            ],
            [
                'numero_proceso' => '4135.010.32.1.250-2026',
                'entidad_contratante' => 'ALCALDÍA DE SANTIAGO DE CALI',
                'objeto' => 'Vigilancia y seguridad privada',
                'estado_proceso' => 'EN PRESENTACIÓN',
                'modalidad_contratacion' => 'Selección abreviada',
            ],
            [
                'numero_proceso' => '25455',
                'entidad_contratante' => 'Entidad demo',
                'objeto' => 'vdfvfbf',
                'estado_proceso' => 'evalución, estudios previos',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fallbackSolicitudes(): array
    {
        return [
            [
                'numero_proceso' => '1523',
                'numero_radicado' => 'RAD-DEMO-1',
                'nombre' => 'Documentación Comeva',
                'estado' => RequestStatus::EnTramite->value,
            ],
            [
                'numero_proceso' => '4135.010.32.1.250-2026',
                'numero_radicado' => 'RAD-DEMO-2',
                'nombre' => 'Documentos Alcaldía Cali',
                'estado' => RequestStatus::Recibido->value,
            ],
            [
                'numero_proceso' => '4135.010.32.1.250-2026',
                'numero_radicado' => 'RAD-DEMO-3',
                'nombre' => 'Anexos técnicos',
                'estado' => RequestStatus::EnTramite->value,
            ],
        ];
    }

    private function toDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
