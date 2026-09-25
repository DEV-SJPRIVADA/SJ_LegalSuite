<?php

namespace Database\Seeders;

use App\Models\Directory\DirectoryUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DirectoryUsersSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/directory_users.json');

        if (! File::exists($path)) {
            $this->command?->warn('No existe database/data/directory_users.json; se omite el directorio.');

            return;
        }

        $rows = json_decode(File::get($path), true);
        if (! is_array($rows)) {
            $this->command?->error('directory_users.json inválido.');

            return;
        }

        $now = now();
        $count = 0;

        foreach ($rows as $row) {
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            DirectoryUser::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => trim((string) ($row['name'] ?? '')) !== ''
                        ? trim((string) $row['name'])
                        : $email,
                    'is_active' => (bool) ($row['is_active'] ?? true),
                    'source' => (string) ($row['source'] ?? 'agendamiento'),
                    'external_id' => isset($row['external_id']) ? (int) $row['external_id'] : null,
                    'updated_at' => $now,
                ]
            );
            $count++;
        }

        $this->command?->info("Directorio corporativo: {$count} funcionarios cargados en directory_users.");
    }
}
