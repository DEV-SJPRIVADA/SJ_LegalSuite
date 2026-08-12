<?php

namespace App\Support\Disciplinary;

/**
 * Catálogo de formatos oficiales del proceso disciplinario (FO-GJ-XX).
 *
 * Convención:
 * - Agregue una fila por cada nuevo formato.
 * - `pdf`: nombre de archivo dentro de `public/formatos/disciplinarios/` o null hasta subir la plantilla.
 * - Algunos códigos pueden generarse en blanco desde HTML (Letter + Browsershot); ver `htmlBlankPdfCodes()`.
 *
 * **Alta de un nuevo formato HTML→PDF (como FO-GJ-51):**
 * 1. Añadir el código en `htmlBlankPdfCodes()` y en `htmlBlankPdfRegistry()` (vista Blade + nombres de archivo).
 * 2. Crear `resources/views/disciplinary/forms/{slug}-blank-download.blade.php` (documento HTML que incluye el layout Letter).
 * 3. Opcional: pantalla de diligenciamiento + `FormRequest` + ruta `POST` (solo FO-GJ-51 tiene flujo completo hoy).
 * 4. Si el código tiene plantilla HTML registrada, esa fuente tiene prioridad sobre PDF estático (ver controladores preview/descarga).
 */
final class OfficialFormsCatalog
{
    /**
     * @return list<array{code: string|null, title: string, phase: string, summary: string, pdf: string|null}>
     */
    public static function all(): array
    {
        return [
            [
                'code' => 'FO-GJ-51',
                'title' => 'Informe disciplinario',
                'phase' => 'A · Falta e informe disciplinario',
                'summary' => 'Etapa inicial del proceso: reporte de la falta y elaboración del informe disciplinario.',
                'pdf' => self::pdfIfExists('FO-GJ-51-informe-disciplinario.pdf'),
            ],
            [
                'code' => 'FO-GJ-03',
                'title' => 'Citación a diligencia disciplinaria por escrito',
                'phase' => 'B · Citación',
                'summary' => 'Citación formal al trabajador para asistencia a la diligencia disciplinaria.',
                'pdf' => self::pdfIfExists('FO-GJ-03-citacion.pdf'),
            ],
            [
                'code' => 'FO-GJ-44',
                'title' => 'Constancia de inasistencia a diligencia disciplinaria',
                'phase' => 'B · Tras no asistencia',
                'summary' => 'Documento que acredita la inasistencia; el trabajador dispone de 2 días calendario para justificar.',
                'pdf' => self::pdfIfExists('FO-GJ-44-constancia-inasistencia.pdf'),
            ],
            [
                'code' => 'FO-GJ-54',
                'title' => 'Reprogramación a diligencia disciplinaria',
                'phase' => 'B · Tras justificación aceptada',
                'summary' => 'Cuando la justificación procede, se reprograma la diligencia disciplinaria.',
                'pdf' => self::pdfIfExists('FO-GJ-54-reprogramacion.pdf'),
            ],
            [
                'code' => 'ACTA-COMITE',
                'title' => 'Comité disciplinario para decisión',
                'phase' => 'C · Comité disciplinario',
                'summary' => 'Cuando no justifica en el plazo o la justificación no procede, el caso puede llevarse al comité disciplinario para decisión.',
                'pdf' => null,
            ],
            [
                'code' => 'FO-GJ-04',
                'title' => 'Acta de diligencia disciplinaria',
                'phase' => 'C · Diligencia y acta',
                'summary' => 'Constancia del desarrollo de la diligencia disciplinaria y levantamiento del acta.',
                'pdf' => self::pdfIfExists('FO-GJ-04-acta-diligencia.pdf'),
            ],
            [
                'code' => 'FO-GJ-45',
                'title' => 'Acta de archivo',
                'phase' => 'D · Decisión / cierre',
                'summary' => 'Acta de archivo del proceso disciplinario (cierre sin sanción escrita: verbal / absuelto / archivado).',
                'pdf' => null,
            ],
            [
                'code' => 'FO-GJ-46',
                'title' => 'Llamado de atención',
                'phase' => 'D · Decisión / cierre',
                'summary' => 'Comunicado de llamado de atención escrito (amonestación escrita) al cierre del proceso disciplinario.',
                'pdf' => null,
            ],
            [
                'code' => 'FO-GJ-47',
                'title' => 'Suspensión disciplinaria',
                'phase' => 'D · Decisión / cierre',
                'summary' => 'Comunicado de suspensión de contrato laboral: días, fechas (inicio con planeación + cálculo de fin/retorno) y fundamento jurídico.',
                'pdf' => null,
            ],
            [
                'code' => 'FO-GJ-DECISION',
                'title' => 'Comunicado de decisión de sanción o cierre del proceso',
                'phase' => 'D · Decisión / cierre',
                'summary' => 'Comunicación genérica de decisión (p. ej. terminación de contrato) cuando no aplica FO-GJ-45/46/47.',
                'pdf' => null,
            ],
            [
                'code' => null,
                'title' => 'Recurso de apelación contra la decisión disciplinaria',
                'phase' => 'E · Apelación',
                'summary' => 'Trámite de apelación interpuesto contra la decisión de primera instancia.',
                'pdf' => null,
            ],
            [
                'code' => null,
                'title' => 'Decisión de segunda instancia',
                'phase' => 'F · Segunda instancia',
                'summary' => 'Resolución definitiva del recurso en segunda instancia.',
                'pdf' => null,
            ],
        ];
    }

    private static function pdfIfExists(string $filename): ?string
    {
        $path = public_path('formatos/disciplinarios/'.$filename);

        return is_file($path) ? $filename : null;
    }

    /**
     * Códigos cuya plantilla en blanco se genera desde HTML (Chrome headless), tamaño carta.
     *
     * @return list<string>
     */
    public static function htmlBlankPdfCodes(): array
    {
        return array_keys(self::htmlBlankPdfRegistry());
    }

    /**
     * Vista Blade (documento HTML Letter) y nombres de archivo PDF por código.
     *
     * @return array<string, array{view: string, inline: string, download: string}>
     */
    public static function htmlBlankPdfRegistry(): array
    {
        return [
            'FO-GJ-51' => [
                'view' => 'disciplinary.forms.fo-gj-51-blank-download',
                'inline' => 'FO-GJ-51-informe-disciplinario-en-blanco.pdf',
                'download' => 'FO-GJ-51-informe-disciplinario-en-blanco.pdf',
            ],
            'FO-GJ-03' => [
                'view' => 'disciplinary.forms.fo-gj-03-blank-download',
                'inline' => 'FO-GJ-03-citacion-en-blanco.pdf',
                'download' => 'FO-GJ-03-citacion-en-blanco.pdf',
            ],
            'FO-GJ-54' => [
                'view' => 'disciplinary.forms.fo-gj-54-blank-download',
                'inline' => 'FO-GJ-54-reprogramacion-en-blanco.pdf',
                'download' => 'FO-GJ-54-reprogramacion-en-blanco.pdf',
            ],
            'FO-GJ-44' => [
                'view' => 'disciplinary.forms.fo-gj-44-blank-download',
                'inline' => 'FO-GJ-44-constancia-inasistencia-en-blanco.pdf',
                'download' => 'FO-GJ-44-constancia-inasistencia-en-blanco.pdf',
            ],
            'FO-GJ-04' => [
                'view' => 'disciplinary.forms.fo-gj-04-blank-download',
                'inline' => 'FO-GJ-04-acta-en-blanco.pdf',
                'download' => 'FO-GJ-04-acta-en-blanco.pdf',
            ],
            'ACTA-COMITE' => [
                'view' => 'disciplinary.forms.comite-acta-blank-download',
                'inline' => 'ACTA-COMITE-en-blanco.pdf',
                'download' => 'ACTA-COMITE-en-blanco.pdf',
            ],
            'FO-GJ-45' => [
                'view' => 'disciplinary.forms.fo-gj-45-blank-download',
                'inline' => 'FO-GJ-45-acta-archivo-en-blanco.pdf',
                'download' => 'FO-GJ-45-acta-archivo-en-blanco.pdf',
            ],
            'FO-GJ-46' => [
                'view' => 'disciplinary.forms.fo-gj-46-blank-download',
                'inline' => 'FO-GJ-46-llamado-atencion-en-blanco.pdf',
                'download' => 'FO-GJ-46-llamado-atencion-en-blanco.pdf',
            ],
            'FO-GJ-47' => [
                'view' => 'disciplinary.forms.fo-gj-47-blank-download',
                'inline' => 'FO-GJ-47-suspension-en-blanco.pdf',
                'download' => 'FO-GJ-47-suspension-en-blanco.pdf',
            ],
            'FO-GJ-DECISION' => [
                'view' => 'disciplinary.forms.decision-comunicado-blank-download',
                'inline' => 'FO-GJ-DECISION-en-blanco.pdf',
                'download' => 'FO-GJ-DECISION-en-blanco.pdf',
            ],
        ];
    }

    public static function htmlBlankPdfView(string $normalizedCode): ?string
    {
        $row = self::htmlBlankPdfRegistry()[strtoupper($normalizedCode)] ?? null;

        return $row['view'] ?? null;
    }

    /**
     * @return array{inline: string, download: string}|null
     */
    public static function htmlBlankPdfFilenames(string $normalizedCode): ?array
    {
        $row = self::htmlBlankPdfRegistry()[strtoupper($normalizedCode)] ?? null;
        if ($row === null) {
            return null;
        }

        return [
            'inline' => $row['inline'],
            'download' => $row['download'],
        ];
    }

    public static function isHtmlBlankPdf(string $normalizedCode): bool
    {
        return isset(self::htmlBlankPdfRegistry()[strtoupper($normalizedCode)]);
    }

    /** Marca de revisión para invalidar caché del iframe (mtime de la vista Blade). */
    public static function htmlBlankPdfRevision(string $normalizedCode): int
    {
        $view = self::htmlBlankPdfView($normalizedCode);
        if ($view === null) {
            return 0;
        }

        $mtime = (int) (@filemtime(view($view)->getPath()) ?: 0);

        if (strtoupper($normalizedCode) === 'FO-GJ-03') {
            $bodyPath = resource_path('views/disciplinary/forms/partials/fo-gj-03-body.blade.php');
            $mtime = max($mtime, (int) (@filemtime($bodyPath) ?: 0));
        }

        if (strtoupper($normalizedCode) === 'FO-GJ-54') {
            $bodyPath = resource_path('views/disciplinary/forms/partials/fo-gj-54-body.blade.php');
            $mtime = max($mtime, (int) (@filemtime($bodyPath) ?: 0));
        }

        if (strtoupper($normalizedCode) === 'FO-GJ-44') {
            $bodyPath = resource_path('views/disciplinary/forms/partials/fo-gj-44-body.blade.php');
            $mtime = max($mtime, (int) (@filemtime($bodyPath) ?: 0));
        }

        if (strtoupper($normalizedCode) === 'FO-GJ-04') {
            $bodyPath = resource_path('views/disciplinary/forms/partials/fo-gj-04-body.blade.php');
            $mtime = max($mtime, (int) (@filemtime($bodyPath) ?: 0));
        }

        if (strtoupper($normalizedCode) === 'ACTA-COMITE') {
            foreach ([
                'partials/comite-acta-body.blade.php',
                'partials/comite-acta-pdf-document.blade.php',
                'partials/comite-acta-pdf-styles.blade.php',
            ] as $relativePath) {
                $bodyPath = resource_path('views/disciplinary/forms/'.$relativePath);
                $mtime = max($mtime, (int) (@filemtime($bodyPath) ?: 0));
            }
        }

        if (strtoupper($normalizedCode) === 'FO-GJ-45') {
            $bodyPath = resource_path('views/disciplinary/forms/partials/fo-gj-45-body.blade.php');
            $mtime = max($mtime, (int) (@filemtime($bodyPath) ?: 0));
        }

        if (strtoupper($normalizedCode) === 'FO-GJ-46') {
            $bodyPath = resource_path('views/disciplinary/forms/partials/fo-gj-46-body.blade.php');
            $mtime = max($mtime, (int) (@filemtime($bodyPath) ?: 0));
        }

        if (strtoupper($normalizedCode) === 'FO-GJ-47') {
            $bodyPath = resource_path('views/disciplinary/forms/partials/fo-gj-47-body.blade.php');
            $mtime = max($mtime, (int) (@filemtime($bodyPath) ?: 0));
        }

        if (strtoupper($normalizedCode) === 'FO-GJ-DECISION') {
            $bodyPath = resource_path('views/disciplinary/forms/partials/decision-comunicado-body.blade.php');
            $mtime = max($mtime, (int) (@filemtime($bodyPath) ?: 0));
        }

        return $mtime;
    }

    /**
     * Hay descarga o vista previa en PDF (archivo estático o generado desde HTML).
     */
    public static function hasBlankPdf(?string $code): bool
    {
        if (! filled($code)) {
            return false;
        }

        $normalized = strtoupper($code);

        if (self::staticBlankPdfAbsolutePath($normalized) !== null) {
            return true;
        }

        return self::isHtmlBlankPdf($normalized);
    }

    /**
     * Ruta absoluta al PDF estático “en blanco” del código FO-GJ, si existe archivo en servidor.
     */
    public static function staticBlankPdfAbsolutePath(string $normalizedCode): ?string
    {
        foreach (self::all() as $row) {
            if (($row['code'] ?? '') !== $normalizedCode || empty($row['pdf'])) {
                continue;
            }
            $path = public_path('formatos/disciplinarios/'.$row['pdf']);

            return is_file($path) ? $path : null;
        }

        return null;
    }
}
