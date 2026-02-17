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

class ConsultorObrasExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    protected $consultorObras;

    public function __construct(Collection $consultorObras)
    {
        $this->consultorObras = $consultorObras;
    }

    public function collection()
    {
        return $this->consultorObras;
    }

    public function headings(): array
    {
        return [
            'N°',
            'CLIENTE',
            'OBJETO DEL CONTRATO',
            'CUI',
            'N° CONTRATO / O/S / COMPROBANTE DE PAGO',
            'FECHA DE CONTRATO O CP',
            'FECHA DE LA CONFORMIDAD DE SER EL CASO',
            'EXPERIENCIA PROVENIENTE DE',
            'MONEDA',
            'MONTO CONTRATADO',
            '% DE PARTICIPACION',
            'IMPORTE',
            'TIPO DE CAMBIO VENTA',
            'MONTO FACTURADO ACUMULADO',
            'N° DE RESOLUCION',
            'FECHA DE APROBACION',
        ];
    }

    public function map($consultorObra): array
    {
        $fecha = function ($d) {
            if (!$d) return '---';
            $date = $d instanceof \Carbon\Carbon ? $d : \Carbon\Carbon::parse($d);
            return $date->format('d/m/Y');
        };
        return [
            '', // N° se rellena en AfterSheet por número de fila
            $consultorObra->entidad ?? '',
            $consultorObra->objeto_contrato ?? '',
            $consultorObra->cui ?? '',
            $consultorObra->numero_contrato_os_comprobante ?? '',
            $fecha($consultorObra->fecha_contrato_cp ?? null),
            $fecha($consultorObra->fecha_conformidad ?? null),
            $consultorObra->experiencia_proveniente_de ?? '---',
            $consultorObra->moneda ?? 'Soles',
            $consultorObra->consorciado ? number_format((float)($consultorObra->monto_contratado ?? 0), 2, '.', ',') : '---',
            $consultorObra->consorciado ? (isset($consultorObra->porcentaje_participacion) ? number_format((float)$consultorObra->porcentaje_participacion, 0) . '%' : '---') : '---',
            number_format((float)($consultorObra->importe ?? 0), 2, '.', ','),
            $consultorObra->tipo_cambio_venta ? number_format((float)$consultorObra->tipo_cambio_venta, 4, '.', ',') : '---',
            number_format((float)($consultorObra->monto_facturado_acumulado ?? 0), 2, '.', ','),
            $consultorObra->numero_resolucion ?? '',
            $fecha($consultorObra->fecha_aprobacion ?? null),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = 'P';
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0E0E0'],
            ],
        ]);

        $sheet->getRowDimension('1')->setRowHeight(30);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,   // N°
            'B' => 35,  // CLIENTE
            'C' => 45,  // OBJETO DEL CONTRATO
            'D' => 12,  // CUI
            'E' => 22,  // N° CONTRATO/O/S/CP
            'F' => 14,  // FECHA CONTRATO
            'G' => 14,  // FECHA CONFORMIDAD
            'H' => 22,  // EXPERIENCIA PROVENIENTE DE
            'I' => 10,  // MONEDA
            'J' => 18,  // MONTO CONTRATADO
            'K' => 16,  // % PARTICIPACION
            'L' => 14,  // IMPORTE
            'M' => 14,  // TIPO CAMBIO
            'N' => 22,  // MONTO FACTURADO ACUMULADO
            'O' => 14,  // N° RESOLUCION
            'P' => 14,  // FECHA APROBACION
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $lastCol = 'P';

                if ($highestRow > 1) {
                    for ($row = 2; $row <= $highestRow; $row++) {
                        $sheet->setCellValue('A' . $row, $row - 1); // N° = 1, 2, 3...
                    }
                    $sheet->getStyle('A2:' . $lastCol . $highestRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                            ],
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                }
            },
        ];
    }
}
