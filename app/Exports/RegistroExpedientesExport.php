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

class RegistroExpedientesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    protected $expedientes;

    public function __construct(Collection $expedientes)
    {
        $this->expedientes = $expedientes;
    }

    public function collection()
    {
        return $this->expedientes;
    }

    public function headings(): array
    {
        return [
            'ETIQUETA',
            'TIPO INVERSIÓN',
            'PROYECTO',
            'CUI',
            'DESCRIPCIÓN',
            'N° FOLIO',
            'TOMOS',
            'AÑO',
            'TIPO UNIDAD CONSERVACIÓN',
            'RESOLUCIÓN',
            'FECHA APROB.',
            'TOTAL MONTOS (S/.)',
            '¿ACT. PRECIOS?',
            '¿REFORMULACIÓN?',
            '¿SUSPENSIÓN?',
        ];
    }

    public function map($item): array
    {
        $fecha = $item->fecha_aprobacion
            ? (\Carbon\Carbon::parse($item->fecha_aprobacion)->format('d/m/Y'))
            : '';
        return [
            $item->etiqueta ?? '',
            $item->tipo_inversion ?? '',
            $item->proyecto ?? '',
            $item->cui ?? '',
            $item->descripcion ?? '',
            $item->numero_folio ?? '',
            $item->tomos ?? '',
            $item->anio ?? '',
            $item->tipo_unidad_conservacion ?? '',
            $item->resolucion ?? '',
            $fecha,
            number_format((float) ($item->monto_total ?? 0), 2, '.', ','),
            $item->tiene_actualizacion_precios ?? '',
            $item->tiene_reformulacion ?? '',
            $item->tuvo_suspension ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:O1')->applyFromArray([
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
            'A' => 14, 'B' => 22, 'C' => 50, 'D' => 14, 'E' => 22, 'F' => 12, 'G' => 18, 'H' => 8,
            'I' => 24, 'J' => 14, 'K' => 14, 'L' => 16, 'M' => 12, 'N' => 14, 'O' => 12,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                if ($highestRow > 1) {
                    $sheet->getStyle('A2:O' . $highestRow)->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                }
            },
        ];
    }
}
