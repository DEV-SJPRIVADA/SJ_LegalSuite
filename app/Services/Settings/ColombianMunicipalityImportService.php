<?php

namespace App\Services\Settings;

use App\Models\ColombianMunicipality;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\CellAddress;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use Throwable;

class ColombianMunicipalityImportService
{
    private const DATA_START_ROW = 3;

    /**
     * @return array{inserted:int, updated:int, skipped:int, errors:array<int, string>}
     */
    public function importFromPath(string $absolutePath, string $extension): array
    {
        $extension = strtolower($extension);

        return match ($extension) {
            'xlsx' => $this->importSpreadsheet($absolutePath),
            'csv' => $this->importCsv($absolutePath),
            default => throw new RuntimeException('Formato no soportado. Use .xlsx o .csv UTF-8.'),
        };
    }

    /**
     * @return array{inserted:int, updated:int, skipped:int, errors:array<int, string>}
     */
    private function importSpreadsheet(string $path): array
    {
        $reader = new XlsxReader;
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        try {
            $sheet = $this->resolveMunicipiosSheet($spreadsheet);

            return $this->persistRows($this->rowsFromWorksheet($sheet));
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    /**
     * @return array{inserted:int, updated:int, skipped:int, errors:array<int, string>}
     */
    private function importCsv(string $path): array
    {
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new RuntimeException('No se pudo leer el archivo CSV.');
        }
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }
        $tmp = tempnam(sys_get_temp_dir(), 'divipola_csv_');
        if ($tmp === false) {
            throw new RuntimeException('No se pudo preparar el CSV temporal.');
        }
        file_put_contents($tmp, $raw);

        try {
            $reader = new CsvReader;
            $reader->setInputEncoding('UTF-8');
            $reader->setTestAutoDetect(true);
            $spreadsheet = $reader->load($tmp);
            try {
                $sheet = $spreadsheet->getActiveSheet();

                return $this->persistRows($this->rowsFromWorksheet($sheet));
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        } finally {
            @unlink($tmp);
        }
    }

    private function resolveMunicipiosSheet(Spreadsheet $spreadsheet): Worksheet
    {
        for ($i = 0; $i < $spreadsheet->getSheetCount(); $i++) {
            $sheet = $spreadsheet->getSheet($i);
            if (strcasecmp(trim((string) $sheet->getTitle()), 'Municipios') === 0) {
                return $sheet;
            }
        }

        throw new RuntimeException('No se encontró la hoja «Municipios». Renombre la hoja o exporte de nuevo el archivo oficial.');
    }

    /**
     * @return list<array{department_code:string, department_name:?string, municipality_code:string, municipality_name:string, municipality_type:?string, longitude:?float, latitude:?float, note:?string}>
     */
    private function rowsFromWorksheet(Worksheet $sheet): array
    {
        $highestRow = (int) $sheet->getHighestRow();
        $rows = [];

        for ($row = self::DATA_START_ROW; $row <= $highestRow; $row++) {
            $deptCode = $this->stringCell($sheet->getCell(CellAddress::fromColumnAndRow(1, $row)));
            $deptName = $this->stringCell($sheet->getCell(CellAddress::fromColumnAndRow(2, $row)));
            $munCodeRaw = $this->stringCell($sheet->getCell(CellAddress::fromColumnAndRow(3, $row)));
            $munName = $this->stringCell($sheet->getCell(CellAddress::fromColumnAndRow(4, $row)));
            $munType = $this->nullableStringCell($sheet->getCell(CellAddress::fromColumnAndRow(5, $row)));
            $lon = $this->coordinateCell($sheet->getCell(CellAddress::fromColumnAndRow(6, $row)));
            $lat = $this->coordinateCell($sheet->getCell(CellAddress::fromColumnAndRow(7, $row)));
            $note = $this->nullableStringCell($sheet->getCell(CellAddress::fromColumnAndRow(8, $row)));

            if ($this->isBlankRow([$deptCode, $deptName, $munCodeRaw, $munName, $munType, $lon, $lat, $note])) {
                continue;
            }

            $munCode = $this->normalizeMunicipalityCode($munCodeRaw);
            if ($munCode === null) {
                throw new RuntimeException("Fila {$row}: código de municipio inválido o vacío (columna C).");
            }

            $deptNorm = $this->normalizeDepartmentCode($deptCode);
            if ($deptNorm === null) {
                throw new RuntimeException("Fila {$row}: código de departamento inválido (columna A).");
            }

            $rows[] = [
                'department_code' => $deptNorm,
                'department_name' => $deptName !== '' ? $deptName : null,
                'municipality_code' => $munCode,
                'municipality_name' => $munName !== '' ? $munName : 'Sin nombre',
                'municipality_type' => $munType,
                'longitude' => $lon,
                'latitude' => $lat,
                'note' => $note,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array{department_code:string, department_name:?string, municipality_code:string, municipality_name:string, municipality_type:?string, longitude:?float, latitude:?float, note:?string}>  $parsed
     * @return array{inserted:int, updated:int, skipped:int, errors:array<int, string>}
     */
    private function persistRows(array $parsed): array
    {
        if ($parsed === []) {
            return ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
        }

        $existing = ColombianMunicipality::query()
            ->whereIn('municipality_code', array_column($parsed, 'municipality_code'))
            ->pluck('municipality_code')
            ->flip()
            ->all();

        $now = now();
        $payload = [];
        foreach ($parsed as $row) {
            $payload[] = array_merge($row, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $errors = [];
        try {
            DB::transaction(function () use ($payload): void {
                ColombianMunicipality::query()->upsert(
                    $payload,
                    ['municipality_code'],
                    [
                        'department_code',
                        'department_name',
                        'municipality_name',
                        'municipality_type',
                        'longitude',
                        'latitude',
                        'note',
                        'updated_at',
                    ]
                );
            });
        } catch (Throwable $e) {
            $errors[] = 'Error al guardar: '.$e->getMessage();

            return ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => $errors];
        }

        $inserted = 0;
        $updated = 0;
        foreach ($parsed as $row) {
            $code = $row['municipality_code'];
            if (isset($existing[$code])) {
                $updated++;
            } else {
                $inserted++;
            }
        }

        return [
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => 0,
            'errors' => [],
        ];
    }

    private function stringCell(Cell $cell): string
    {
        $v = $cell->getValue();

        if ($v === null) {
            return '';
        }

        if ($v instanceof RichText) {
            return trim($v->getPlainText());
        }

        if (is_numeric($v) && ! is_string($v)) {
            if (floor((float) $v) == (float) $v) {
                return (string) (int) $v;
            }

            return rtrim(rtrim((string) $v, '0'), '.');
        }

        return trim((string) $v);
    }

    private function nullableStringCell(Cell $cell): ?string
    {
        $s = $this->stringCell($cell);

        return $s === '' ? null : $s;
    }

    private function coordinateCell(Cell $cell): ?float
    {
        $s = $this->stringCell($cell);
        if ($s === '') {
            return null;
        }
        $s = str_replace(',', '.', $s);
        if (! is_numeric($s)) {
            return null;
        }

        return (float) $s;
    }

    /**
     * @param  list<?float|string|null>  $parts
     */
    private function isBlankRow(array $parts): bool
    {
        foreach ($parts as $p) {
            if ($p === null) {
                continue;
            }
            if (is_string($p) && trim($p) !== '') {
                return false;
            }
            if (is_float($p)) {
                return false;
            }
        }

        return true;
    }

    private function normalizeMunicipalityCode(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) > 5) {
            return null;
        }
        $digits = str_pad($digits, 5, '0', STR_PAD_LEFT);
        if (strlen($digits) !== 5) {
            return null;
        }

        return $digits;
    }

    private function normalizeDepartmentCode(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) > 2) {
            return null;
        }

        return str_pad($digits, 2, '0', STR_PAD_LEFT);
    }
}
