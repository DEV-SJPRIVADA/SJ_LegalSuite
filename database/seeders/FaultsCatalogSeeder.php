<?php

namespace Database\Seeders;

use App\Enums\Disciplinary\FaultSeverity;
use App\Models\Disciplinary\Fault;
use Illuminate\Database\Seeder;

class FaultsCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $faults = [
            ['code' => 'F-001', 'name' => 'Retardo al servicio', 'severity' => FaultSeverity::LEVE],
            ['code' => 'F-002', 'name' => 'Abandono del puesto', 'severity' => FaultSeverity::GRAVE],
            ['code' => 'F-003', 'name' => 'Actitud poco alerta (dormido)', 'severity' => FaultSeverity::GRAVE],
            ['code' => 'F-004', 'name' => 'Síntomas de alicoramiento', 'severity' => FaultSeverity::GRAVE],
            ['code' => 'F-005', 'name' => 'No porta uniforme adecuadamente', 'severity' => FaultSeverity::LEVE],
            ['code' => 'F-006', 'name' => 'Incumplimiento de consignas', 'severity' => FaultSeverity::MEDIA],
            ['code' => 'F-007', 'name' => 'Ausencia al servicio', 'severity' => FaultSeverity::GRAVE],
            ['code' => 'F-008', 'name' => 'Daño en dotación', 'severity' => FaultSeverity::MEDIA],
            ['code' => 'F-009', 'name' => 'Cambio por solicitud del cliente', 'severity' => FaultSeverity::MEDIA],
            ['code' => 'F-010', 'name' => 'Mala presentación personal', 'severity' => FaultSeverity::LEVE],
            ['code' => 'F-011', 'name' => 'Descuido de dotación', 'severity' => FaultSeverity::MEDIA],
            ['code' => 'F-012', 'name' => 'Incumplimiento de instrucciones', 'severity' => FaultSeverity::MEDIA],
            ['code' => 'F-013', 'name' => 'Irrespeto', 'severity' => FaultSeverity::GRAVE],
            ['code' => 'F-014', 'name' => 'Incautación de arma', 'severity' => FaultSeverity::GRAVE],
            ['code' => 'F-999', 'name' => 'Otros', 'severity' => FaultSeverity::MEDIA, 'requires_extra_info' => true],
        ];

        foreach ($faults as $i => $row) {
            Fault::updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'severity' => $row['severity'],
                    'requires_extra_info' => $row['requires_extra_info'] ?? false,
                    'is_active' => true,
                    'sort_order' => $i + 1,
                ]
            );
        }
    }
}
