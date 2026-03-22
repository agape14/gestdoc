<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class EjecutorObrasExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    protected $obras;

    /** Acumulado para MONTO ACUMULADO en Excel: primera fila = Monto Neto, siguientes = anterior + Monto Neto */
    protected $montoAcumulado = 0;

    public function __construct(Collection $obras)
    {
        $this->obras = $obras;
        $this->montoAcumulado = 0;
    }

    public function collection()
    {
        return $this->obras;
    }

    /**
     * Calcula Monto Acumulado para la fila: toma el Monto Neto de la fila actual,
     * lo suma al acumulado y devuelve el valor formateado.
     */
    protected function formatMontoAcumulado($obra): string
    {
        $montoNeto = (float)($obra->monto_neto ?? 0);
        $this->montoAcumulado += $montoNeto;
        return number_format($this->montoAcumulado, 2, '.', ',');
    }

    public function headings(): array
    {
        return [
            'N°',
            'Nombre o Sigla de la Entidad',
            'Nomenclatura',
            'Descripción de Objeto',
            'CUI',
            '# CONTRATO',
            'FECHA DE FIRMA DE CONTRATO',
            'Monto Total (S/.)',
            'PLAZO (días)',
            'Fecha de Inicio',
            'Fecha Suspensión',
            'Fecha Reinicio',
            'Fecha Final',
            '% Participación',
            'Monto Neto (S/.)',
            'Monto Acumulado (S/.)',
            'Liquidado y/o recepcionado',
            'FECHA DE ENTREGA DE TERRENO',
            'FECHA DE LA RECEPCION DE OBRA',
            'FECHA DE LA APROBACION DE LIQUIDACION DE OBRA',
        ];
    }

    public function map($obra): array
    {
        $fecha = function ($d) {
            if (!$d) return '';
            return $d instanceof \Carbon\Carbon ? $d->format('d/m/Y') : (is_string($d) && strlen($d) >= 10 ? substr($d, 0, 10) : $d);
        };
        return [
            $obra->id ?? '',
            $obra->nombre_sigla_entidad ?? '',
            $obra->nomenclatura ?? '',
            $obra->descripcion_objeto ?? '',
            $obra->cui ?? '',
            $obra->numero_contrato ?? '',
            $fecha($obra->fecha_firma_contrato),
            number_format((float)($obra->monto_total ?? 0), 2, '.', ','),
            $obra->plazo ?? '',
            $fecha($obra->fecha_inicio),
            $fecha($obra->fecha_suspension),
            $fecha($obra->fecha_reinicio),
            $fecha($obra->fecha_final),
            $obra->porcentaje_participacion !== null ? number_format((float)$obra->porcentaje_participacion, 2, '.', ',') . '%' : '',
            number_format((float)($obra->monto_neto ?? 0), 2, '.', ','),
            $this->formatMontoAcumulado($obra),
            !empty($obra->liquidado_recepcionado) ? 'SI' : 'NO',
            $fecha($obra->fecha_entrega_terreno),
            $fecha($obra->fecha_recepcion_obra),
            $fecha($obra->fecha_aprobacion_liquidacion),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:T1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
        ]);
        $sheet->getRowDimension('1')->setRowHeight(30);
        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6, 'B' => 35, 'C' => 30, 'D' => 45, 'E' => 14, 'F' => 16, 'G' => 14, 'H' => 16,
            'I' => 10, 'J' => 14, 'K' => 14, 'L' => 14, 'M' => 14, 'N' => 14, 'O' => 14, 'P' => 16,
            'Q' => 16, 'R' => 12, 'S' => 18, 'T' => 18,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                if ($highestRow > 1) {
                    $sheet->getStyle('A2:T' . $highestRow)->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                }
            },
        ];
    }
}
