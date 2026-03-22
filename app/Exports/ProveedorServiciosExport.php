<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Exportación de experiencia (proveedor de servicios): incluye TRASLAPE y TOTAL DIAS SIN TRASLAPE
 * aunque estén vacíos o en cero según reglas de negocio.
 */
class ProveedorServiciosExport implements FromCollection, WithHeadings, WithMapping
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
            'CLIENTE',
            'OBJETO DEL CONTRATO',
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
            'ESPECIALIDAD',
            'TIPO SERVICIO',
            'CATEGORÍA',
            'CLASIFICACIÓN',
            'TÍTULO',
            'ENTIDAD',
            'ESTADO',
            'TIPO DOCUMENTO ADJUNTO',
        ];
    }

    public function map($s): array
    {
        return [
            $s->cliente ?? '',
            $s->objeto_del_contrato ?? '',
            $s->numero_contrato_os_comprobante ?? '',
            optional($s->fecha_inicio)?->format('d/m/Y') ?? '',
            optional($s->fecha_suspension)?->format('d/m/Y') ?? '',
            optional($s->fecha_reinicio)?->format('d/m/Y') ?? '',
            optional($s->fecha_culminacion)?->format('d/m/Y') ?? '',
            $s->total_meses !== null && $s->total_meses !== '' ? (string) $s->total_meses : '',
            $s->total_dias !== null && $s->total_dias !== '' ? (string) $s->total_dias : '',
            $s->traslape !== null && $s->traslape !== '' ? (string) $s->traslape : '',
            $s->total_dias_sin_traslape !== null && $s->total_dias_sin_traslape !== '' ? (string) $s->total_dias_sin_traslape : '',
            $s->monto_neto !== null ? number_format((float) $s->monto_neto, 2, '.', ',') : '',
            $s->monto_acumulado !== null ? number_format((float) $s->monto_acumulado, 2, '.', ',') : '',
            $s->especialidad ?? '',
            $s->tipo_servicio ?? '',
            $s->categoria ?? '',
            $s->clasificacion ?? '',
            $s->titulo ?? '',
            $s->entidad ?? '',
            $s->estado ?? '',
            $s->tipo_documento_adjunto ?? '',
        ];
    }
}
