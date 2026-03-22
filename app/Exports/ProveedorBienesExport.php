<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProveedorBienesExport implements FromCollection, WithHeadings, WithMapping
{
    protected $bienes;

    public function __construct(Collection $bienes)
    {
        $this->bienes = $bienes;
    }

    public function collection()
    {
        return $this->bienes;
    }

    public function headings(): array
    {
        return [
            'CLIENTE',
            'OBJETO DEL CONTRATO',
            'N° CONTRATO / O/C / COMPROBANTE',
            'FECHA INICIO',
            'FECHA CULMINACION',
            'TOTAL DIAS',
            'MONTO NETO',
            'MONTO ACUMULADO',
        ];
    }

    public function map($bien): array
    {
        return [
            $bien->cliente ?? '',
            $bien->objeto_del_contrato ?? '',
            $bien->numero_contrato_oc_comprobante ?? '',
            optional($bien->fecha_inicio)?->format('d/m/Y') ?? '',
            optional($bien->fecha_culminacion)?->format('d/m/Y') ?? '',
            $bien->total_dias ?? '',
            number_format((float) ($bien->monto_neto ?? 0), 2, '.', ','),
            number_format((float) ($bien->monto_acumulado ?? 0), 2, '.', ','),
        ];
    }
}
