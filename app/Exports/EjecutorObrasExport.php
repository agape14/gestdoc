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

    public function __construct(Collection $obras)
    {
        $this->obras = $obras;
    }

    public function collection()
    {
        return $this->obras;
    }

    public function headings(): array
    {
        return [
            'PROYECTO',
            'ENTIDAD',
            'ESPECIALIDAD',
            'TIPO',
            'PRESUPUESTO',
            'ESTADO',
            'MODALIDAD',
        ];
    }

    public function map($obra): array
    {
        return [
            $obra->titulo ?? '',
            $obra->entidad ?? '',
            $obra->especialidad ?? '',
            $obra->tipo_obra ?? '',
            number_format($obra->presupuesto ?? 0, 2, '.', ','),
            $obra->estado ?? '',
            $obra->modalidad ?? '',
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
            'A' => 30,  // PROYECTO
            'B' => 25,  // ENTIDAD
            'C' => 20,  // ESPECIALIDAD
            'D' => 20,  // TIPO
            'E' => 18,  // PRESUPUESTO
            'F' => 15,  // ESTADO
            'G' => 20,  // MODALIDAD
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
