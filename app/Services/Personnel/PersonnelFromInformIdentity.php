<?php

namespace App\Services\Personnel;

use App\Models\Personnel;
use Illuminate\Support\Str;

/**
 * Resuelve o crea un registro de personal a partir del nombre y documento declarados en un informe FO-GJ-51.
 */
class PersonnelFromInformIdentity
{
    public function resolve(string $fullName, string $documentNumber): Personnel
    {
        $normalizedDoc = $this->normalizeDocument($documentNumber);
        $name = trim(preg_replace('/\s+/u', ' ', $fullName));

        if ($normalizedDoc === '' || $name === '') {
            throw new \InvalidArgumentException('Nombre y documento son obligatorios para vincular al trabajador.');
        }

        $existing = Personnel::withTrashed()
            ->where('document_number', $normalizedDoc)
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            return $existing;
        }

        [$firstName, $lastName] = $this->splitFullName($name);

        return Personnel::create([
            'document_type' => 'CC',
            'document_number' => $normalizedDoc,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'is_active' => true,
        ]);
    }

    public function normalizeDocument(string $documentNumber): string
    {
        $digits = preg_replace('/\s+/u', '', trim($documentNumber));

        return Str::upper($digits);
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitFullName(string $name): array
    {
        $tokens = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($tokens === []) {
            throw new \InvalidArgumentException('Nombre no válido.');
        }

        if (count($tokens) === 1) {
            return [$tokens[0], '-'];
        }

        $lastName = array_pop($tokens);

        return [implode(' ', $tokens), $lastName];
    }
}
