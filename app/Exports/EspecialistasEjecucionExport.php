<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Incluye TRASLAPE y TOTAL DÍAS SIN TRASLAPE para exportación (aunque en UI no se editen).
 */
class EspecialistasEjecucionExport implements FromCollection, WithHeadings, WithMapping
{
    protected $rows;

    public function __construct(Collection $rows)
    {
        $this->rows = $rows;
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'CLIENTE',
            'OBJETO DEL CONTRATO',
            'CUI',
            'N° CONTRATO / O/S / COMPROBANTE',
            'FECHA INICIO',
            'FECHA SUSPENSIÓN',
            'FECHA REINICIO',
            'FECHA CULMINACIÓN',
            'TOTAL MESES',
            'TOTAL DÍAS',
            'TRASLAPE',
            'TOTAL DÍAS SIN TRASLAPE',
            'MONTO NETO',
            'MONTO ACUMULADO',
            'NOMBRE',
            'ESPECIALIDAD',
            'TIPO',
            'ESTADO',
            'CLASIFICACIÓN',
            'TIPO DOCUMENTO ADJUNTO',
        ];
    }

    public function map($r): array
    {
        return [
            $r->cliente ?? '',
            $r->objeto_del_contrato ?? '',
            $r->cui ?? '',
            $r->numero_contrato_os_comprobante ?? '',
            optional($r->fecha_inicio)?->format('d/m/Y') ?? '',
            optional($r->fecha_suspension)?->format('d/m/Y') ?? '',
            optional($r->fecha_reinicio)?->format('d/m/Y') ?? '',
            optional($r->fecha_culminacion)?->format('d/m/Y') ?? '',
            $r->total_meses !== null && $r->total_meses !== '' ? (string) $r->total_meses : '',
            $r->total_dias !== null && $r->total_dias !== '' ? (string) $r->total_dias : '',
            $r->traslape !== null && $r->traslape !== '' ? (string) $r->traslape : '',
            $r->total_dias_sin_traslape !== null && $r->total_dias_sin_traslape !== '' ? (string) $r->total_dias_sin_traslape : '',
            $r->monto_neto !== null ? number_format((float) $r->monto_neto, 2, '.', ',') : '',
            $r->monto_acumulado !== null ? number_format((float) $r->monto_acumulado, 2, '.', ',') : '',
            $r->nombre ?? '',
            $r->especialidad ?? '',
            $r->tipo ?? '',
            $r->estado ?? '',
            $r->clasificacion ?? '',
            $r->tipo_documento_adjunto ?? '',
        ];
    }
}
