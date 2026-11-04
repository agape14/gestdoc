<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class RegistroExpedientesExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, WithEvents, WithCustomValueBinder
{
    protected Collection $expedientes;

    protected string $sortBy;

    public function __construct(Collection $expedientes, string $sortBy = 'etiqueta')
    {
        $this->expedientes = $expedientes;
        $this->sortBy = $sortBy;
    }

    public function collection(): Collection
    {
        return $this->expedientes->values();
    }

    public function headings(): array
    {
        return [
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
    }

    public function map($e): array
    {
        $o = (float) ($e->monto_o ?? 0);
        $p = (float) ($e->monto_p ?? 0);
        $s = (float) ($e->monto_s ?? 0);
        $sup = (float) ($e->monto_supervision ?? 0);
        $total = $o + $p + $s + $sup;

        $fecha = null;
        if (! empty($e->fecha_aprobacion)) {
            try {
                $fecha = ExcelDate::PHPToExcel(Carbon::parse($e->fecha_aprobacion));
            } catch (\Throwable) {
                $fecha = null;
            }
        }

        $estado = $e->estado ?: (($e->tipo_accion ?? null) === 'LIQUIDACION' ? 'ARCHIVADO' : 'EN CURSO');

        return [
            $e->tipo_inversion ?? '',
            $e->etiqueta ?? '',
            $e->proyecto ?? '',
            (string) ($e->cui ?? ''),
            $estado,
            $e->descripcion ?? '',
            $e->numero_folio ?? '',
            $e->tomos ?? '',
            $e->anio !== null && $e->anio !== '' ? (int) $e->anio : '',
            $e->tipo_unidad_conservacion ?? '',
            $e->resolucion ?? '',
            $fecha,
            $e->monto_o === null || $e->monto_o === '' ? null : $o,
            $e->monto_p === null || $e->monto_p === '' ? null : $p,
            $e->monto_s === null || $e->monto_s === '' ? null : $s,
            $e->monto_supervision === null || $e->monto_supervision === '' ? null : $sup,
            $total,
            self::labelAccion($e->tipo_accion ?? null),
            self::archivoNombre($e->contrato ?? null),
            self::archivoNombre($e->resolucion_archivo ?? null),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'L' => 'DD/MM/YYYY',
            'M' => '"S/" #,##0.00',
            'N' => '"S/" #,##0.00',
            'O' => '"S/" #,##0.00',
            'P' => '"S/" #,##0.00',
            'Q' => '"S/" #,##0.00',
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        if ($cell->getColumn() === 'D' && $cell->getRow() > 1) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    protected static function labelAccion(?string $tipo): string
    {
        if (! $tipo) {
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

    protected static function archivoNombre(?string $path): string
    {
        if (! $path) {
            return '';
        }

        return basename(str_replace('\\', '/', $path));
    }

    public function registerEvents(): array
    {
        $sortBy = $this->sortBy;

        return [
            AfterSheet::class => function (AfterSheet $event) use ($sortBy) {
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

                if (in_array($sortBy, ['etiqueta', 'tipo_inversion'], true)) {
                    $mergeCols = ['A', 'B', 'C', 'D'];
                    $start = 2;
                    for ($r = 3; $r <= $highestRow + 1; $r++) {
                        $sameGroup = $r <= $highestRow
                            && (string) $sheet->getCell('A' . $r)->getValue() === (string) $sheet->getCell('A' . $start)->getValue()
                            && (string) $sheet->getCell('B' . $r)->getValue() === (string) $sheet->getCell('B' . $start)->getValue()
                            && (string) $sheet->getCell('C' . $r)->getValue() === (string) $sheet->getCell('C' . $start)->getValue()
                            && (string) $sheet->getCell('D' . $r)->getValue() === (string) $sheet->getCell('D' . $start)->getValue();
                        if (! $sameGroup) {
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
