<?php

namespace App\Console\Commands;

use App\Models\Directory\DirectoryUser;
use App\Services\Directory\CompanyDirectorySyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SyncCompanyDirectoryCommand extends Command
{
    protected $signature = 'directory:sync
                            {--from=agendamiento : Origen: agendamiento|json}';

    protected $description = 'Sincroniza el directorio corporativo (Agendamiento o JSON embebido) hacia directory_users';

    public function handle(CompanyDirectorySyncService $sync): int
    {
        $from = strtolower((string) $this->option('from'));

        if ($from === 'json') {
            return $this->syncFromJson();
        }

        $this->info('Sincronizando directorio desde Agendamiento…');

        try {
            $result = $sync->syncFromAgendamiento();
        } catch (\Throwable $e) {
            $this->error('No se pudo sincronizar desde Agendamiento: '.$e->getMessage());
            $this->line('En Hostinger puede usar: php artisan directory:sync --from=json');
            $this->line('O: php artisan db:seed --class=DirectoryUsersSeeder');

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Listo. Fuente: %d | nuevos: %d | actualizados: %d | desactivados: %d',
            $result['total_source'],
            $result['imported'],
            $result['updated'],
            $result['deactivated']
        ));

        return self::SUCCESS;
    }

    private function syncFromJson(): int
    {
        $path = database_path('data/directory_users.json');
        if (! File::exists($path)) {
            $this->error('No existe '.$path);

            return self::FAILURE;
        }

        $this->call('db:seed', ['--class' => 'Database\\Seeders\\DirectoryUsersSeeder', '--force' => true]);
        $this->info('directory_users activos: '.DirectoryUser::query()->active()->count());

        return self::SUCCESS;
    }
}
