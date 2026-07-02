<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida un data URI PNG capturado en pantalla (lienzo de firma).
 */
class PngSignatureDataUri implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $dataUri = trim((string) $value);

        if ($dataUri === '') {
            return;
        }

        if (! preg_match('#^data:image/png;base64,[A-Za-z0-9+/=\s]+$#', $dataUri)) {
            $fail('La firma debe capturarse en el lienzo (formato PNG).');

            return;
        }

        $raw = base64_decode(preg_replace('#\s+#', '', substr($dataUri, 22)) ?: '', true);

        if ($raw === false || strlen($raw) < 32) {
            $fail('La firma capturada no es válida.');
        }
    }
}
