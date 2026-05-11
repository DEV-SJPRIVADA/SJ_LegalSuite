<?php

namespace Tests\Unit;

use App\Models\Disciplinary\Fault;
use App\Support\Disciplinary\FoGj51Catalog;
use App\Support\Disciplinary\FoGj51SnapshotFaultMapper;
use Database\Seeders\FaultsCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoGj51SnapshotFaultMapperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FaultsCatalogSeeder::class);
    }

    public function test_maps_multiple_checkbox_labels_to_distinct_faults(): void
    {
        $snapshot = [
            'fo51_fault_left' => ['Ausencia al servicio', 'Descuido con elementos de puesto y/o dotación'],
            'fo51_fault_right' => ['Daño con elementos de puesto y/o dotación', 'Incumplimiento de consignas'],
        ];

        $rows = FoGj51SnapshotFaultMapper::pivotRowsFromSnapshot($snapshot, 'Resumen de prueba');

        $this->assertCount(4, $rows);
        $codes = Fault::query()->whereIn('id', array_column($rows, 'fault_id'))->pluck('code')->sort()->values()->all();
        $this->assertSame(['F-006', 'F-007', 'F-008', 'F-011'], $codes);
    }

    public function test_empty_snapshot_falls_back_to_single_f006(): void
    {
        $rows = FoGj51SnapshotFaultMapper::pivotRowsFromSnapshot([], 'Solo observaciones');

        $this->assertCount(1, $rows);
        $this->assertSame('F-006', Fault::query()->findOrFail($rows[0]['fault_id'])->code);
        $this->assertNotNull($rows[0]['extra_info']);
    }

    public function test_every_catalog_checkbox_label_maps_to_a_fault(): void
    {
        foreach (array_merge(FoGj51Catalog::faultLeft(), FoGj51Catalog::faultRight()) as $label) {
            $rows = FoGj51SnapshotFaultMapper::pivotRowsFromSnapshot([
                'fo51_fault_left' => [$label],
            ], 'Prueba');

            $this->assertCount(1, $rows, 'Falta mapeo para la etiqueta: '.$label);
        }
    }
}
