<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('employees')
            ->whereNull('hired_at')
            ->update([
                'hired_at' => DB::raw('DATE(created_at)'),
            ]);

        DB::table('employees')
            ->whereNull('contract_type')
            ->update([
                'contract_type' => 'termino_indefinido',
            ]);

        DB::table('employees')
            ->whereNotNull('residence_municipality_code')
            ->whereNull('residence_department_code')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $dept = DB::table('colombian_municipalities')
                        ->where('municipality_code', $row->residence_municipality_code)
                        ->value('department_code');

                    if ($dept) {
                        DB::table('employees')->where('id', $row->id)->update([
                            'residence_department_code' => $dept,
                        ]);
                    }
                }
            });

        DB::table('employees')
            ->whereNotNull('municipality_code')
            ->whereNull('work_department_code')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $dept = DB::table('colombian_municipalities')
                        ->where('municipality_code', $row->municipality_code)
                        ->value('department_code');

                    if ($dept) {
                        DB::table('employees')->where('id', $row->id)->update([
                            'work_department_code' => $dept,
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        // No reversible: los valores inferidos no pueden distinguirse de los originales.
    }
};
