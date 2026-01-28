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
            'PROYECTO',
            'ENTIDAD',
            'ESPECIALIDAD',
            'TIPO',
            'PRESUPUESTO',
            'ESTADO',
            'DURACION',
            'MODALIDAD',
        ];
    }

    public function map($consultorObra): array
    {
        return [
            $consultorObra->titulo ?? '',
            $consultorObra->entidad ?? '',
            $consultorObra->especialidad ?? '',
            $consultorObra->tipo_servicio ?? '',
            number_format($consultorObra->presupuesto ?? 0, 2, '.', ','),
            $consultorObra->estado ?? '',
            $consultorObra->duracion ?? '',
            $consultorObra->modalidad ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:H1')->applyFromArray([
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
            'D' => 25,  // TIPO
            'E' => 18,  // PRESUPUESTO
            'F' => 15,  // ESTADO
            'G' => 15,  // DURACION
            'H' => 20,  // MODALIDAD
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                if ($highestRow > 1) {
                    $sheet->getStyle('A2:H' . $highestRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                            ],
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    // Agrupar por especialidad
                    $groupedData = [];
                    for ($row = 2; $row <= $highestRow; $row++) {
                        $especialidad = $sheet->getCell('C' . $row)->getValue();
                        if (!isset($groupedData[$especialidad])) {
                            $groupedData[$especialidad] = [];
                        }
                        $groupedData[$especialidad][] = $row;
                    }

                    // Aplicar estilos por grupo de especialidad
                    foreach ($groupedData as $especialidad => $rows) {
                        if (count($rows) > 1) {
                            $firstRow = min($rows);
                            $lastRow = max($rows);
                            $sheet->getStyle('C' . $firstRow . ':C' . $lastRow)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'F5F5F5'],
                                ],
                            ]);
                        }
                    }
                }
            },
        ];
    }
}
