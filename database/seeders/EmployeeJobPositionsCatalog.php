<?php

namespace Database\Seeders;

use App\Enums\EmployeeScope;

/**
 * Catálogo oficial de cargos RRHH (carga masiva y formulario de empleados).
 */
class EmployeeJobPositionsCatalog
{
    /**
     * @return list<array{name: string, employee_scope: string, is_guarda: bool}>
     */
    public static function rows(): array
    {
        $administrativo = EmployeeScope::Administrativo->value;
        $operativo = EmployeeScope::Operativo->value;

        $administrativeNames = [
            'ANALISTA COMERCIAL',
            'ANALISTA CONTABLE',
            'ANALISTA DE COMPRAS',
            'ANALISTA DE FORMACION',
            'ANALISTA DE MARKETING DIGITAL',
            'ANALISTA DE NOMINA',
            'ANALISTA DE OPERACIONES',
            'ANALISTA DE PROGRAMACION',
            'ANALISTA DE RELACIONES LABORALES Y CUMPLIMIENTO',
            'ANALISTA DE SELECCION',
            'ANALISTA JURIDICO',
            'ANALISTA TIC Y SISTEMAS',
            'APRENDIZ SENA - ET LECTIVA',
            'APRENDIZ SENA - ET PRODUCTIVA',
            'ASISTENTE COMERCIAL',
            'ASISTENTE CONTABLE DOS',
            'ASISTENTE CONTABLE TRES',
            'ASISTENTE CONTABLE UNO',
            'ASISTENTE DE COMPRAS Y DOTACION',
            'ASISTENTE DE GESTION DOCUMENTAL',
            'ASISTENTE DE GESTION HUMANA',
            'ASISTENTE DE NOMINA',
            'ASISTENTE DE SELECCION',
            'ASISTENTE DE SERVICIO AL CLIENTE',
            'ASISTENTE TECNICO',
            'AUXILIAR TIC',
            'COORD DE TECNOLOGÍA Y CM',
            'COORDINACION COMERCIAL',
            'COORDINACIÓN DE PROYECTOS TECNOLOGICOS',
            'COORDINACION ESTRATEGICA DE UNIONES TEMPORALES',
            'COORDINACION GESTION HUMANA',
            'COORDINADOR DE PROGRAMACION',
            'COORDINADOR (A)',
            'COORDINADOR DE OPERACIONES',
            'COORDINADOR(A) CONTABLE',
            'COORDINADORA SST',
            'DIRECCION GESTION HUMANA',
            'DIRECCIÓN COMERCIAL',
            'DIRECCION FINANCIERA Y ADMON',
            'DIRECCION NACIONAL JURIDICA Y LICITACIONES',
            'DIRECTOR NACIONAL DE RIESGOS',
            'EJECUTIVO COMERCIAL DE PROYECTOS TECNOLOGICOS',
            'EJECUTIVO(A) COMERCIAL',
            'ESTRATEGA DE MARKETING Y VENTAS',
            'GERENTE',
            'JEFE DE CALIDAD Y SERV AL CLIENTE',
            'JEFE DE OPERACIONES',
            'JEFE DE PLANEACIÓN Y PROGRAMACIÓN',
            'PROGRAMADOR TIC',
        ];

        $rows = [];
        $sort = 10;

        foreach ($administrativeNames as $name) {
            $rows[] = [
                'name' => $name,
                'employee_scope' => $administrativo,
                'is_guarda' => false,
                'sort_order' => $sort,
            ];
            $sort += 10;
        }

        foreach ([
            ['name' => 'GUARDA DE SEGURIDAD', 'is_guarda' => true],
            ['name' => 'GUARDA MOTORIZADO (C/A)', 'is_guarda' => true],
            ['name' => 'GUARDA MOTORIZADO (S/A)', 'is_guarda' => true],
            ['name' => 'ESCOLTA', 'is_guarda' => true],
            ['name' => 'MANEJADOR CANINO.', 'is_guarda' => true],
            ['name' => 'SUPERVISOR', 'is_guarda' => false],
            ['name' => 'OPERADOR', 'is_guarda' => false],
            ['name' => 'MENSAJEROS', 'is_guarda' => false],
        ] as $operational) {
            $rows[] = [
                'name' => $operational['name'],
                'employee_scope' => $operativo,
                'is_guarda' => $operational['is_guarda'],
                'sort_order' => $sort,
            ];
            $sort += 10;
        }

        return $rows;
    }
}
