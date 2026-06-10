<?php

namespace App\Services\Users;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UserSignatureService
{
    public function store(User $user, UploadedFile $file): User
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'signatureFile' => 'No se pudo leer el archivo de firma.',
            ]);
        }

        $disk = 'local';
        $directory = 'signatures/'.$user->id;

        if ($user->signature_path && $user->signature_disk) {
            Storage::disk($user->signature_disk)->delete($user->signature_path);
        }

        $path = $file->store($directory, $disk);

        $user->forceFill([
            'signature_path' => $path,
            'signature_disk' => $disk,
        ])->save();

        return $user->fresh();
    }

    public function remove(User $user): User
    {
        if ($user->signature_path && $user->signature_disk) {
            Storage::disk($user->signature_disk)->delete($user->signature_path);
        }

        $user->forceFill([
            'signature_path' => null,
            'signature_disk' => null,
        ])->save();

        return $user->fresh();
    }

    public function dataUriForPdf(User $user): ?string
    {
        if (! $user->hasSignature()) {
            return null;
        }

        $disk = Storage::disk($user->signature_disk ?? 'local');
        if (! $disk->exists((string) $user->signature_path)) {
            return null;
        }

        $contents = $disk->get((string) $user->signature_path);
        $mime = $disk->mimeType((string) $user->signature_path) ?: 'image/png';

        return sprintf('data:%s;base64,%s', $mime, base64_encode($contents));
    }
}
