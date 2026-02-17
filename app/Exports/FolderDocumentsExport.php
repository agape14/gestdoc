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

class FolderDocumentsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    protected Collection $documents;

    public function __construct(Collection $documents)
    {
        $this->documents = $documents;
    }

    public function collection()
    {
        return $this->documents;
    }

    public function headings(): array
    {
        return [
            'N°',
            'Número',
            'Fecha',
            'Asunto',
            'Remitente',
            'Destinatario',
            'Referencia',
            'Archivos',
            'Folios',
        ];
    }

    public function map($doc): array
    {
        $fecha = $doc->fecha_documento
            ? (\Carbon\Carbon::parse($doc->fecha_documento))->format('d/m/Y')
            : '—';
        $archivos = $doc->files && $doc->files->isNotEmpty()
            ? $doc->files->pluck('nombre_archivo')->implode(', ')
            : '—';
        return [
            '', // N° se rellena en AfterSheet
            $doc->numero ?? '—',
            $fecha,
            $doc->asunto ?? '—',
            $doc->remitente ?? '—',
            $doc->destinatario ?? '—',
            $doc->referencia ?? '—',
            $archivos,
            (int) ($doc->folios ?? 0),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = 'I';
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
        $sheet->getRowDimension('1')->setRowHeight(24);
        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 14,
            'C' => 12,
            'D' => 40,
            'E' => 25,
            'F' => 25,
            'G' => 25,
            'H' => 40,
            'I' => 8,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $lastCol = 'I';
                if ($highestRow > 1) {
                    for ($row = 2; $row <= $highestRow; $row++) {
                        $sheet->setCellValue('A' . $row, $row - 1);
                    }
                    $sheet->getStyle('A2:' . $lastCol . $highestRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                            ],
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                    ]);
                }
            },
        ];
    }
}
