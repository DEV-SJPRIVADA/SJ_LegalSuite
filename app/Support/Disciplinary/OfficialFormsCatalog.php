<?php

namespace App\Support\Disciplinary;

/**
 * Catálogo de formatos oficiales del proceso disciplinario (FO-GJ-XX).
 *
 * Convención:
 * - Agregue una fila por cada nuevo formato.
 * - `pdf`: nombre de archivo dentro de `public/formatos/disciplinarios/` o null hasta subir la plantilla.
 * - Algunos códigos pueden generarse en blanco desde HTML (Letter + Browsershot); ver `htmlBlankPdfCodes()`.
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
                'code' => null,
                'title' => 'Constancia de inasistencia a citación',
                'phase' => 'B · Tras no asistencia',
                'summary' => 'Documento que acredita la inasistencia; el trabajador dispone de 2 días calendario para justificar.',
                'pdf' => null,
            ],
            [
                'code' => 'FO-GJ-54',
                'title' => 'Reprogramación a diligencia disciplinaria',
                'phase' => 'B · Tras justificación aceptada',
                'summary' => 'Cuando la justificación procede, se reprograma la diligencia disciplinaria.',
                'pdf' => self::pdfIfExists('FO-GJ-54-reprogramacion.pdf'),
            ],
            [
                'code' => null,
                'title' => 'Comité disciplinario para decisión',
                'phase' => 'B · Sin justificación (u otro ingreso a comité)',
                'summary' => 'Cuando no justifica en el plazo o la justificación no procede, el caso puede llevarse al comité disciplinario para decisión.',
                'pdf' => null,
            ],
            [
                'code' => 'FO-GJ-42',
                'title' => 'Acta de diligencia disciplinaria',
                'phase' => 'C · Diligencia y acta',
                'summary' => 'Constancia del desarrollo de la diligencia disciplinaria y levantamiento del acta.',
                'pdf' => self::pdfIfExists('FO-GJ-42-acta-diligencia.pdf'),
            ],
            [
                'code' => null,
                'title' => 'Comunicado de decisión de sanción o cierre del proceso',
                'phase' => 'D · Decisión / cierre',
                'summary' => 'Comunicación al trabajador de la decisión (sanción) o del cierre del proceso cuando aplique.',
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
        return ['FO-GJ-51'];
    }

    public static function isHtmlBlankPdf(string $normalizedCode): bool
    {
        return in_array(strtoupper($normalizedCode), self::htmlBlankPdfCodes(), true);
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
