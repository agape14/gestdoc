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

class LicitacionesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    protected $licitaciones;

    public function __construct(Collection $licitaciones)
    {
        $this->licitaciones = $licitaciones;
    }

    public function collection()
    {
        return $this->licitaciones;
    }

    public function headings(): array
    {
        return [
            'LICITACIÓN',
            'PROYECTO',
            'ENTIDAD',
            'ESPECIALIDAD',
            'PRESUPUESTO',
            'MODALIDAD',
            'CONSORCIO',
            'R.C.',
            'CONSORCIO',
            'CONSORCIADOS',
            'ESTADO',
        ];
    }

    public function map($licitacion): array
    {
        $consorciados = '';
        if ($licitacion->consorcio && $licitacion->consorciados) {
            $consorciadosArray = is_array($licitacion->consorciados) ? $licitacion->consorciados : json_decode($licitacion->consorciados, true);
            if ($consorciadosArray) {
                $consorciadosList = [];
                foreach ($consorciadosArray as $item) {
                    if (isset($item['nombre']) && isset($item['porcentaje'])) {
                        $consorciadosList[] = $item['nombre'] . ' (' . $item['porcentaje'] . '%)';
                    }
                }
                $consorciados = implode(', ', $consorciadosList);
            }
        }

        return [
            $licitacion->titulo ?? '',
            $licitacion->titulo ?? '',
            $licitacion->entidad ?? '',
            $licitacion->especialidad ?? '',
            number_format($licitacion->presupuesto ?? 0, 2, '.', ','),
            $licitacion->modalidad ?? '',
            $licitacion->consorcio ? 'Sí' : 'No',
            $licitacion->nombre_rc ?? '',
            $licitacion->nombre_consorcio ?? '',
            $consorciados,
            $licitacion->estado ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:K1')->applyFromArray([
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
            'A' => 30,  // LICITACIÓN
            'B' => 30,  // PROYECTO
            'C' => 25,  // ENTIDAD
            'D' => 20,  // ESPECIALIDAD
            'E' => 18,  // PRESUPUESTO
            'F' => 20,  // MODALIDAD
            'G' => 12,  // CONSORCIO
            'H' => 20,  // R.C.
            'I' => 20,  // CONSORCIO
            'J' => 40,  // CONSORCIADOS
            'K' => 15,  // ESTADO
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                if ($highestRow > 1) {
                    $sheet->getStyle('A2:K' . $highestRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                            ],
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    // Agrupar por especialidad si hay múltiples registros
                    $groupedData = [];
                    for ($row = 2; $row <= $highestRow; $row++) {
                        $especialidad = $sheet->getCell('D' . $row)->getValue();
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
                            $sheet->getStyle('D' . $firstRow . ':D' . $lastRow)->applyFromArray([
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
