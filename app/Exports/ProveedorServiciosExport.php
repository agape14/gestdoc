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

class ProveedorServiciosExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    protected $servicios;

    public function __construct(Collection $servicios)
    {
        $this->servicios = $servicios;
    }

    public function collection()
    {
        return $this->servicios;
    }

    public function headings(): array
    {
        return [
            'SERVICIO',
            'ENTIDAD',
            'ESPECIALIDAD',
            'TIPO',
            'PRESUPUESTO',
            'ESTADO',
            'DURACION',
        ];
    }

    public function map($servicio): array
    {
        return [
            $servicio->titulo ?? '',
            $servicio->entidad ?? '',
            $servicio->especialidad ?? '',
            $servicio->tipo_servicio ?? '',
            number_format($servicio->presupuesto ?? 0, 2, '.', ','),
            $servicio->estado ?? '',
            $servicio->duracion ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:G1')->applyFromArray([
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
            'A' => 30,  // SERVICIO
            'B' => 25,  // ENTIDAD
            'C' => 20,  // ESPECIALIDAD
            'D' => 20,  // TIPO
            'E' => 18,  // PRESUPUESTO
            'F' => 15,  // ESTADO
            'G' => 15,  // DURACION
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                if ($highestRow > 1) {
                    $sheet->getStyle('A2:G' . $highestRow)->applyFromArray([
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
