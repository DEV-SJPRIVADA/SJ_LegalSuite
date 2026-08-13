<?php

namespace Database\Seeders;

use App\Models\Disciplinary\DiligenceActaQuestion;
use Illuminate\Database\Seeder;

class DiligenceActaQuestionsSeeder extends Seeder
{
    public function run(): void
    {
        if (DiligenceActaQuestion::query()->exists()) {
            return;
        }

        $questions = [
            'Reconoce los hechos descritos en la citación a diligencia disciplinaria',
            'Desea realizar alguna aclaración o precisión sobre los hechos reportados',
            'Tenía conocimiento de las obligaciones y procedimientos aplicables a su cargo',
            'Informó oportunamente a su supervisor o a la empresa sobre la situación ocurrida',
            'Desea aportar pruebas o documentos adicionales para su defensa',
            'Tiene algo más que agregar en ejercicio de su derecho de defensa',
        ];

        foreach ($questions as $index => $text) {
            DiligenceActaQuestion::query()->create([
                'text' => $text,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
