import React from 'react';
import ModuleIndex from '@/Components/ModuleIndex';
import ExperienciaPieTabla from '@/Components/ExperienciaPieTabla';
import { formatDateDisplay, formatMonedaPeruana } from '@/Utils/experienciaCalculations';

export default function Index({ servicios, experienceTotals = {}, filters, userRole, folders = [], currentFolder = null, breadcrumb = [], operadores = [] }) {
    const labelTipo = (t) => ({ CONTRATO: 'Contrato', COMPROBANTE_DE_PAGO: 'Comprobante de pago', CONFORMIDAD_DE_SERVICIO: 'Conformidad de servicio' }[t] || t);
    const getDocumentLinks = (item) => {
        if (item.archivo_contrato_url)
            return [{ label: labelTipo(item.tipo_documento_adjunto) || 'Archivo', path: item.archivo_contrato, url: item.archivo_contrato_url }];
        return [];
    };

    const columns = [
        { header: 'N°', accessor: 'id', render: (item, rowIndex) => ((servicios.current_page || 1) - 1) * (servicios.per_page || 10) + (rowIndex ?? 0) + 1 },
        { header: 'CLIENTE', accessor: 'cliente' },
        { header: 'OBJETO DEL CONTRATO', accessor: 'objeto_del_contrato', render: (item) => (item.objeto_del_contrato ? String(item.objeto_del_contrato).slice(0, 80) + (String(item.objeto_del_contrato).length > 80 ? '...' : '') : '-') },
        { header: 'N° CONTRATO / O/S / COMPROBANTE DE PAGO', accessor: 'numero_contrato_os_comprobante' },
        { header: 'FECHA DE INICIO', accessor: 'fecha_inicio', render: (item) => formatDateDisplay(item.fecha_inicio) },
        { header: 'FECHA DE SUSPENSIÓN', accessor: 'fecha_suspension', render: (item) => formatDateDisplay(item.fecha_suspension) },
        { header: 'FECHA DE REINICIO', accessor: 'fecha_reinicio', render: (item) => formatDateDisplay(item.fecha_reinicio) },
        { header: 'FECHA DE CULMINACION', accessor: 'fecha_culminacion', render: (item) => formatDateDisplay(item.fecha_culminacion) },
        { header: 'TOTAL DE MESES', accessor: 'total_meses', render: (item) => item.total_meses != null ? Number(item.total_meses).toFixed(2) : '-' },
        { header: 'TOTAL DE DIAS', accessor: 'total_dias' },
        { header: 'TRASLAPE', accessor: 'traslape', render: (item) => item.traslape != null ? Number(item.traslape).toFixed(2) : '0.00' },
        { header: 'TOTAL DE DIAS SIN TRASLAPE', accessor: 'total_dias_sin_traslape', render: (item) => item.total_dias_sin_traslape != null ? `${item.total_dias_sin_traslape} Dias Calendario` : '-' },
        { header: 'Monto Neto', accessor: 'monto_neto', render: (item) => formatMonedaPeruana(item.monto_neto) },
        { header: 'Monto Acumulado', accessor: 'monto_acumulado', render: (item) => formatMonedaPeruana(item.monto_acumulado) },
        { header: 'ACCIONES', accessor: 'actions' },
    ];

    return (
            <ModuleIndex
                renderFooter={
                    <ExperienciaPieTabla
                        totalDiasSinTraslape={experienceTotals.total_dias_sin_traslape ?? 0}
                        totalMontoAcumulado={experienceTotals.total_monto_acumulado ?? 0}
                        showMonto
                    />
                }
                getDocumentLinks={getDocumentLinks}
                title="Proveedor de Servicios"
                description="EXPERIENCIA EN LA ESPECIALIDAD"
                items={servicios}
                columns={columns}
                createRoute={currentFolder?.id ? route('proveedor-servicios.create', { folder_id: currentFolder.id }) : route('proveedor-servicios.create')}
                editRoute={(id) => `/proveedor-servicios/${id}/edit`}
                deleteRoute="proveedor-servicios.destroy"
                filters={[]}
                routeParams={filters}
                userRole={userRole}
                folders={folders}
                currentFolder={currentFolder}
                breadcrumb={breadcrumb}
                storeFolderRoute="proveedor-servicios.folders.store"
                indexRoute="proveedor-servicios.index"
                indexTitle="Proveedor de Servicios"
                operadores={operadores}
            />
    );
}
