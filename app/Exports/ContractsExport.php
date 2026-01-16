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

class ContractsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    protected $contracts;

    public function __construct(Collection $contracts)
    {
        $this->contracts = $contracts;
    }

    public function collection()
    {
        return $this->contracts;
    }

    public function headings(): array
    {
        return [
            'N°',
            'CLIENTE',
            'OBJETO DEL CONTRATO',
            'N° CONTRATO / O/S / COMPROBANTE',
            'FECHA DE CONTRATO O CP',
            'FECHA DE LA CONFORMIDA D DE SER EL CASO',
            'EXPERIENCIA PROVENIENTE DE:',
            'MONEDA',
            'MONTO CONTRATADO',
            'PORCENTAJE DE PARTICIPACION',
            'MONTO FACTURADO',
            'TIPO DE CAMBIO',
            'MONTO FACTURADO ACUMULADO',
        ];
    }

    public function map($contract): array
    {
        static $index = 0;
        $index++;

        $montoFacturado = $contract->amount;
        if ($contract->participation_percentage < 100) {
            $montoFacturado = ($contract->amount * $contract->participation_percentage) / 100;
        }

        return [
            $index,
            $contract->client ?? '',
            $contract->contract_object ?? '',
            $contract->contract_number ?? '',
            $contract->contract_date ? \Carbon\Carbon::parse($contract->contract_date)->format('d/m/Y') : '',
            $contract->conformity_date ? \Carbon\Carbon::parse($contract->conformity_date)->format('d/m/Y') : '-----',
            '-----',
            $contract->currency === 'USD' ? 'Dólares' : 'Soles',
            number_format($contract->amount, 2, '.', ','),
            $contract->participation_percentage < 100 ? number_format($contract->participation_percentage, 2) : '',
            number_format($montoFacturado, 2, '.', ','),
            $contract->exchange_rate ? number_format($contract->exchange_rate, 4) : '-----',
            '', // Lo calcularemos en AfterSheet
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo para el encabezado
        $sheet->getStyle('A1:M1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 10,
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

        // Altura de la fila de encabezado
        $sheet->getRowDimension('1')->setRowHeight(40);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,   // N°
            'B' => 30,  // CLIENTE
            'C' => 50,  // OBJETO DEL CONTRATO
            'D' => 20,  // N° CONTRATO
            'E' => 15,  // FECHA DE CONTRATO
            'F' => 15,  // FECHA DE LA CONFORMIDAD
            'G' => 20,  // EXPERIENCIA
            'H' => 12,  // MONEDA
            'I' => 20,  // MONTO CONTRATADO
            'J' => 12,  // PORCENTAJE
            'K' => 20,  // MONTO FACTURADO
            'L' => 12,  // TIPO DE CAMBIO
            'M' => 20,  // MONTO FACTURADO ACUMULADO
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // Calcular montos acumulados
                $acumulado = 0;
                for ($row = 2; $row <= $highestRow; $row++) {
                    // Obtener el monto facturado (columna K)
                    $montoFacturado = $sheet->getCell('K' . $row)->getValue();

                    // Remover formato de número (comas) y convertir a float
                    $montoFacturado = (float) str_replace(',', '', $montoFacturado);

                    // Sumar al acumulado
                    $acumulado += $montoFacturado;

                    // Escribir el acumulado en la columna M
                    $sheet->setCellValue('M' . $row, number_format($acumulado, 2, '.', ','));
                }

                // Aplicar estilos a las celdas de datos
                $sheet->getStyle('A2:M' . $highestRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Centrar columnas específicas
                $sheet->getStyle('A2:A' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('D2:D' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('E2:E' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('F2:F' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('G2:G' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('H2:H' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('J2:J' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('L2:L' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Alinear a la derecha las columnas de montos
                $sheet->getStyle('I2:I' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('K2:K' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('M2:M' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Agregar fila de TOTAL
                $totalRow = $highestRow + 1;
                $sheet->setCellValue('A' . $totalRow, 'TOTAL');
                $sheet->mergeCells('A' . $totalRow . ':L' . $totalRow);
                $sheet->setCellValue('M' . $totalRow, number_format($acumulado, 2, '.', ','));

                // Estilo para la fila de TOTAL
                $sheet->getStyle('A' . $totalRow . ':M' . $totalRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 11,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F0F0F0'],
                    ],
                ]);

                // Alinear el monto total a la derecha
                $sheet->getStyle('M' . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Agregar fecha y firma
                $fechaRow = $totalRow + 3;
                $firmaRow = $fechaRow + 8;

                // Fecha
                $fechaActual = \Carbon\Carbon::now()->locale('es')->isoFormat('D [de] MMMM [del] YYYY');
                $sheet->setCellValue('A' . $fechaRow, 'San Marcos, ' . $fechaActual);
                $sheet->getStyle('A' . $fechaRow)->getFont()->setSize(11);

                // Línea de firma
                $sheet->setCellValue('F' . $firmaRow, '_____________________________________________');
                $sheet->getStyle('F' . $firmaRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Texto de firma
                $textoFirmaRow = $firmaRow + 1;
                $sheet->setCellValue('F' . $textoFirmaRow, 'Firma, Nombres y Apellidos del postor o');
                $sheet->setCellValue('F' . ($textoFirmaRow + 1), 'Representante legal o Común, según corresponda');

                $sheet->getStyle('F' . $textoFirmaRow . ':F' . ($textoFirmaRow + 1))->applyFromArray([
                    'font' => [
                        'italic' => true,
                        'size' => 10,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // Merge cells para el texto de firma
                $sheet->mergeCells('F' . $textoFirmaRow . ':I' . $textoFirmaRow);
                $sheet->mergeCells('F' . ($textoFirmaRow + 1) . ':I' . ($textoFirmaRow + 1));
            },
        ];
    }
}
