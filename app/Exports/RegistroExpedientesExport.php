<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class RegistroExpedientesExport implements FromArray, WithEvents
{
    protected Collection $expedientes;

    public function __construct(Collection $expedientes)
    {
        $this->expedientes = $expedientes;
    }

    protected static function fmtMoney($v): string
    {
        if ($v === null || $v === '') {
            return '';
        }
        $n = (float) $v;

        return number_format($n, 2, ',', ' ');
    }

    protected static function labelAccion(?string $tipo): string
    {
        if (!$tipo) {
            return '—';
        }
        $map = [
            'ADICIONAL' => 'ADICIONAL',
            'ADICIONAL_CON_DEDUCTIVO' => 'ADICIONAL CON DEDUCTIVO',
            'DEDUCTIVO' => 'DEDUCTIVO',
            'ACTUALIZACION_PRECIOS' => 'ACTUALIZACIÓN DE PRECIOS',
            'REFORMULACION' => 'REFORMULACIÓN',
        ];

        return $map[$tipo] ?? $tipo;
    }

    /** Orden numérico del N° para listar 4, 5, 1005 de forma lógica. */
    protected static function numeroSortKey($numero): array
    {
        $s = trim((string) ($numero ?? ''));
        if ($s !== '' && ctype_digit($s)) {
            return [0, (int) $s];
        }

        return [1, $s];
    }

    /** Orden natural de etiqueta: 04, 07, 10, 100. */
    protected static function etiquetaSortKey($etiqueta): array
    {
        $s = trim((string) ($etiqueta ?? ''));
        if (preg_match('/^(\d+)/', $s, $m)) {
            return [0, (int) $m[1], $s];
        }

        return [1, PHP_INT_MAX, $s];
    }

    protected static function archivoNombre(?string $path): string
    {
        if (!$path) {
            return '';
        }

        return basename(str_replace('\\', '/', $path));
    }

    public function array(): array
    {
        $sorted = $this->expedientes->sortBy(function ($e) {
            return [
                (string) ($e->tipo_inversion ?? ''),
                self::etiquetaSortKey($e->etiqueta ?? ''),
                (string) ($e->proyecto ?? ''),
                (string) ($e->cui ?? ''),
                self::numeroSortKey($e->numero ?? ''),
                $e->id,
            ];
        })->values();

        $headings = [
            'TIPO DE INVERSIÓN',
            'ETIQUETA',
            'PROYECTO',
            'CUI',
            'ESTADO',
            'DESCRIPCIÓN',
            'N° DE FOLIO',
            'TOMOS',
            'AÑO',
            'TIPO DE UNIDADES DE CONSERVACIÓN',
            'RESOLUCIÓN',
            'FECHA DE APROBACIÓN',
            'EXPEDIENTE TÉCNICO',
            'EVAL.',
            'PPTO DE OBRA',
            'SUPERVISIÓN',
            'TOTAL',
            'TIPO ACCIÓN',
            'CONTRATO (ARCHIVO)',
            'RESOLUCIÓN (ARCHIVO)',
        ];

        $rows = [$headings];
        $prevGroupKey = null;

        foreach ($sorted as $e) {
            $groupKey = implode("\x1e", [
                (string) ($e->tipo_inversion ?? ''),
                (string) ($e->etiqueta ?? ''),
                (string) ($e->proyecto ?? ''),
                (string) ($e->cui ?? ''),
            ]);
            $firstOfGroup = $groupKey !== $prevGroupKey;
            $prevGroupKey = $groupKey;

            $fecha = $e->fecha_aprobacion
                ? \Carbon\Carbon::parse($e->fecha_aprobacion)->format('d/m/Y')
                : '';

            $o = (float) ($e->monto_o ?? 0);
            $p = (float) ($e->monto_p ?? 0);
            $s = (float) ($e->monto_s ?? 0);
            $sup = (float) ($e->monto_supervision ?? 0);
            $total = $o + $p + $s + $sup;

            $tipoAccion = self::labelAccion($e->tipo_accion ?? null);

            $estado = $e->estado ?: (($e->tipo_accion ?? null) === 'LIQUIDACION' ? 'ARCHIVADO' : 'EN CURSO');

            $rows[] = [
                $firstOfGroup ? ($e->tipo_inversion ?? '') : '',
                $firstOfGroup ? ($e->etiqueta ?? '') : '',
                $firstOfGroup ? ($e->proyecto ?? '') : '',
                $firstOfGroup ? ($e->cui ?? '') : '',
                $estado,
                $e->descripcion ?? '',
                $e->numero_folio ?? '',
                $e->tomos ?? '',
                $e->anio ?? '',
                $e->tipo_unidad_conservacion ?? '',
                $e->resolucion ?? '',
                $fecha,
                self::fmtMoney($e->monto_o),
                self::fmtMoney($e->monto_p),
                self::fmtMoney($e->monto_s),
                self::fmtMoney($e->monto_supervision),
                self::fmtMoney($total),
                $tipoAccion,
                self::archivoNombre($e->contrato ?? null),
                self::archivoNombre($e->resolucion_archivo ?? null),
            ];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestCol = 'T';

                $sheet->getStyle('A1:' . $highestCol . '1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
                ]);

                if ($highestRow > 1) {
                    $sheet->getStyle('A2:' . $highestCol . $highestRow)->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
                    ]);
                }

                // Combinar A–D (tipo, etiqueta, proyecto, CUI) cuando varias filas comparten el mismo grupo.
                $mergeCols = ['A', 'B', 'C', 'D'];
                $start = 2;
                for ($r = 3; $r <= $highestRow + 1; $r++) {
                    $newGroup = $r > $highestRow
                        || (string) $sheet->getCell('A' . $r)->getValue() !== '';
                    if ($newGroup) {
                        $end = $r - 1;
                        if ($end > $start) {
                            foreach ($mergeCols as $col) {
                                $sheet->mergeCells($col . $start . ':' . $col . $end);
                                $sheet->getStyle($col . $start)->getAlignment()
                                    ->setVertical(Alignment::VERTICAL_CENTER)
                                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                                    ->setWrapText(true);
                            }
                        }
                        $start = $r;
                    }
                }

                foreach (range('A', $highestCol) as $col) {
                    $w = match ($col) {
                        'C' => 42,
                        'B' => 12,
                        'F' => 28,
                        'R' => 22,
                        'S', 'T' => 24,
                        default => 14,
                    };
                    $sheet->getColumnDimension($col)->setWidth($w);
                }
            },
        ];
    }
}
