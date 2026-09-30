<?php

namespace App\Console\Commands;

use App\Models\Directory\DirectoryUser;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Crea/actualiza usuarios de login a partir de directory_users (p. ej. en Hostinger).
 * Genera contraseñas provisionales en storage/app/directory_provisioned_credentials.csv
 */
class ProvisionDirectoryLoginUsersCommand extends Command
{
    protected $signature = 'directory:provision-users
                            {--keep-admin=soporte.admin@sjsp.com.co : Email de admin que no se toca (roles/permisos)}
                            {--dry-run : Solo muestra cuántos se procesarían}';

    protected $description = 'Crea usuarios de la app desde directory_users (módulo Licitaciones + CSV de contraseñas)';

    public function handle(): int
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $perms = [
            'licitaciones.view',
            'licitaciones.upload-document',
            'module.licitaciones',
        ];

        foreach ($perms as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        $keepAdmin = strtolower(trim((string) $this->option('keep-admin')));
        $dryRun = (bool) $this->option('dry-run');

        $credentialsPath = storage_path('app/directory_provisioned_credentials.csv');
        $fh = null;

        if (! $dryRun) {
            $fh = fopen($credentialsPath, 'w');
            if ($fh === false) {
                $this->error('No se pudo escribir '.$credentialsPath);

                return self::FAILURE;
            }
            fputcsv($fh, ['name', 'email', 'password_provisional']);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        $directory = DirectoryUser::query()->active()->orderBy('name')->get();

        foreach ($directory as $person) {
            $email = strtolower(trim((string) $person->email));
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }

            if ($keepAdmin !== '' && $email === $keepAdmin) {
                $this->line("Omitido admin: {$email}");
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $exists = User::query()->withTrashed()->whereRaw('LOWER(email) = ?', [$email])->exists();
                $this->line(($exists ? '[update] ' : '[create] ').$email);
                $exists ? $updated++ : $created++;
                continue;
            }

            $plain = Str::password(14, true, true, true, false);
            $name = trim((string) $person->name) !== '' ? trim((string) $person->name) : $email;

            DB::transaction(function () use ($email, $name, $plain, $perms, $fh, &$created, &$updated) {
                $user = User::query()->withTrashed()->whereRaw('LOWER(email) = ?', [$email])->first();

                if ($user) {
                    if ($user->trashed()) {
                        $user->restore();
                    }
                    $user->forceFill([
                        'name' => $name,
                        'password' => Hash::make($plain),
                        'is_active' => true,
                        'read_only' => false,
                        'must_change_password' => true,
                        'email_verified_at' => $user->email_verified_at ?? now(),
                    ])->save();
                    $updated++;
                } else {
                    $user = User::query()->create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make($plain),
                        'is_active' => true,
                        'read_only' => false,
                        'must_change_password' => true,
                        'email_verified_at' => now(),
                        'organizational_area_id' => null,
                        'job_position_id' => null,
                    ]);
                    $created++;
                }

                $user->syncRoles([]);
                $user->syncPermissions($perms);

                fputcsv($fh, [$user->name, $user->email, $plain]);
            });
        }

        if ($fh !== null) {
            fclose($fh);
        }

        $this->newLine();
        $this->info($dryRun
            ? "Dry-run. Crearían: {$created} · actualizarían: {$updated} · omitidos: {$skipped}"
            : "Listo. Creados: {$created} · actualizados: {$updated} · omitidos: {$skipped}"
        );

        if (! $dryRun) {
            $this->line('Contraseñas provisionales: '.$credentialsPath);
            $this->line('Usuarios en tabla users: '.User::query()->count());
        }

        return self::SUCCESS;
    }
}
