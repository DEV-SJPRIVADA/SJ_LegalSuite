<?php

namespace Database\Seeders;

use App\Enums\UserArea;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crea un usuario por cada rol/área para poder probar el sistema localmente.
 * Las contraseñas son las mismas en local — cambiarlas antes de cualquier deploy.
 */
class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['email' => 'admin@sjlegalsuite.local', 'name' => 'Administrador', 'role' => 'admin', 'area' => UserArea::JURIDICA],
            ['email' => 'juridico@sjlegalsuite.local', 'name' => 'Abogado Jurídico', 'role' => 'juridico', 'area' => UserArea::JURIDICA],
            ['email' => 'gerencia@sjlegalsuite.local', 'name' => 'Gerencia', 'role' => 'gerencia', 'area' => UserArea::GERENCIA],
            ['email' => 'auditor@sjlegalsuite.local', 'name' => 'Auditor', 'role' => 'auditor', 'area' => UserArea::ADMINISTRATIVA],
            ['email' => 'operaciones@sjlegalsuite.local', 'name' => 'Operaciones', 'role' => 'operaciones', 'area' => UserArea::OPERACIONES],
        ];

        foreach ($defaults as $row) {
            $user = User::updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => Hash::make('SJseguridad2026'),
                    'area' => $row['area'],
                    'is_active' => true,
                ],
            );
            $user->syncRoles([$row['role']]);
        }
    }
}
