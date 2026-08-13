<?php

namespace App\Services;

use App\Models\Disciplinary\DisciplinaryCase;
use App\Models\User;
use App\Services\Disciplinary\DisciplinaryDashboardService;

/**
 * Tablero de inicio (command center) exclusivo para administradores del suite.
 */
class HomeDashboardService
{
    public function __construct(
        private readonly AlertsService $alerts,
        private readonly DisciplinaryDashboardService $disciplinary,
    ) {}

    /**
     * @return array{
     *     summary: array<string, mixed>,
     *     trend: list<array{month:string, total:int}>,
     *     kpis: array<string, mixed>,
     *     workflow: array<string, mixed>,
     *     lawyerWorkload: list<array<string, mixed>>,
     *     caseMapPins: list<array<string, mixed>>,
     *     topMunicipalities: list<array<string, mixed>>,
     *     casesWithoutMunicipalityCount: int,
     *     criticalAlertCount: int,
     *     totalAlerts: int,
     * }
     */
    public function build(User $admin): array
    {
        $summary = $this->alerts->summary(8, $admin);
        $kpis = $this->disciplinary->kpis($admin);
        $caseMapPins = $this->disciplinary->casesByMunicipalityMapPins($admin);

        $criticalAlertCount = (int) $summary['vencidos']['count']
            + (int) $summary['sin_asignar']['count'];

        $totalAlerts = (int) $summary['vencidos']['count']
            + (int) $summary['proximos']['count']
            + (int) $summary['sin_asignar']['count']
            + (int) $summary['pendientes_decision']['count'];

        return [
            'summary' => $summary,
            'trend' => $this->alerts->monthlyTrend(6, $admin),
            'kpis' => $kpis,
            'workflow' => $this->disciplinary->workflowStageDonuts($admin),
            'lawyerWorkload' => array_slice($this->disciplinary->lawyerWorkload($admin), 0, 5),
            'caseMapPins' => $caseMapPins,
            'topMunicipalities' => $this->topMunicipalitiesFromPins($caseMapPins, 5),
            'casesWithoutMunicipalityCount' => DisciplinaryCase::query()
                ->forDisciplinaryActor($admin)
                ->whereNull('municipality_code')
                ->count(),
            'criticalAlertCount' => $criticalAlertCount,
            'totalAlerts' => $totalAlerts,
        ];
    }

    /**
     * @param  list<array{code:string, label:string, lat:float, lon:float, count:int}>  $pins
     * @return list<array{code:string, label:string, count:int}>
     */
    private function topMunicipalitiesFromPins(array $pins, int $limit): array
    {
        $sorted = $pins;
        usort($sorted, fn (array $a, array $b) => $b['count'] <=> $a['count'] ?: strcmp($a['label'], $b['label']));

        return array_map(
            fn (array $pin) => [
                'code' => $pin['code'],
                'label' => $pin['label'],
                'count' => $pin['count'],
            ],
            array_slice($sorted, 0, $limit)
        );
    }
}
