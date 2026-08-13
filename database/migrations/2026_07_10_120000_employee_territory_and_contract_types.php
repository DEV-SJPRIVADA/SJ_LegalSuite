<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('residence_department_code', 2)->nullable()->after('residence_municipality_code');
            $table->string('work_department_code', 2)->nullable()->after('municipality_code');
        });

        DB::table('employees')
            ->where('contract_type', 'aprendizaje')
            ->update(['contract_type' => 'aprendizaje_lectiva']);

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
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['residence_department_code', 'work_department_code']);
        });
    }
};
