import React from 'react';
import ModuleIndex from '@/Components/ModuleIndex';
import ModuleIndexRowDetail from '@/Components/ModuleIndexRowDetail';
import ExperienciaPieTabla from '@/Components/ExperienciaPieTabla';
import { formatDateDisplay, formatMonedaPeruana } from '@/Utils/experienciaCalculations';

export default function Index({ especialistas, experienceTotals = {}, filters, userRole, folders = [], currentFolder = null, breadcrumb = [], operadores = [] }) {
    const labelTipo = (t) => ({ CONTRATO: 'Contrato', COMPROBANTE_DE_PAGO: 'Comprobante de pago', CONFORMIDAD_DE_SERVICIO: 'Conformidad de servicio' }[t] || t);
    const getDocumentLinks = (item) => {
        if (item.archivo_contrato_url)
            return [{ label: labelTipo(item.tipo_documento_adjunto) || 'Archivo', path: item.archivo_contrato, url: item.archivo_contrato_url }];
        return item.documento ? [{ label: 'Documento', path: item.documento, url: item.documento_url }] : [];
    };

    const columns = [
        { header: 'N°', accessor: 'id', render: (item, rowIndex) => ((especialistas.current_page || 1) - 1) * (especialistas.per_page || 10) + (rowIndex ?? 0) + 1 },
        { header: 'CLIENTE', accessor: 'cliente', render: (item) => (item.cliente ? String(item.cliente).slice(0, 35) + (String(item.cliente).length > 35 ? '…' : '') : '-') },
        { header: 'OBJETO', accessor: 'objeto_del_contrato', render: (item) => (item.objeto_del_contrato ? String(item.objeto_del_contrato).slice(0, 45) + (String(item.objeto_del_contrato).length > 45 ? '…' : '') : '-') },
        { header: 'CUI', accessor: 'cui' },
        { header: 'FECHA INICIO', accessor: 'fecha_inicio', render: (item) => formatDateDisplay(item.fecha_inicio) },
        { header: 'FECHA CULM.', accessor: 'fecha_culminacion', render: (item) => formatDateDisplay(item.fecha_culminacion) },
        { header: 'MONTO NETO', accessor: 'monto_neto', render: (item) => formatMonedaPeruana(item.monto_neto) },
        { header: '', accessor: '_expand', render: () => <i className="bi bi-chevron-down text-secondary" title="Ver detalle" /> },
    ];

    const getDetailFields = (item) => [
        { label: 'N° contrato / O/S / Comprobante', value: item.numero_contrato_os_comprobante },
        { label: 'Fecha suspensión', value: formatDateDisplay(item.fecha_suspension) },
        { label: 'Fecha reinicio', value: formatDateDisplay(item.fecha_reinicio) },
        { label: 'Total meses', value: item.total_meses != null ? Number(item.total_meses).toFixed(2) : '' },
        { label: 'Total días', value: item.total_dias },
        { label: 'Traslape', value: item.traslape != null ? Number(item.traslape).toFixed(2) : '' },
        { label: 'Total días sin traslape', value: item.total_dias_sin_traslape != null ? `${item.total_dias_sin_traslape} días` : '' },
        { label: 'Monto acumulado', value: item.monto_acumulado != null ? formatMonedaPeruana(item.monto_acumulado) : '' },
        { label: 'Objeto del contrato (completo)', value: item.objeto_del_contrato },
    ];

    const renderDetail = (item, context) => (
        <ModuleIndexRowDetail
            item={item}
            userRole={userRole}
            fields={getDetailFields(item)}
            editHref={`/especialistas-consultoria/${item.id}/edit`}
            deleteRouteName="especialistas-consultoria.destroy"
            documentButton={getDocumentLinks(item).length > 0 && context?.openDocumentsModal ? (
                <button type="button" className="btn btn-sm btn-outline-primary" onClick={(e) => { e.stopPropagation(); context.openDocumentsModal(item); }}>
                    <i className="bi bi-file-earmark-pdf me-1"></i> Ver documento
                </button>
            ) : null}
        />
    );

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
            title="Especialistas en Consultoría de Obra"
            description="Experiencia en la especialidad. Clic en la fila para ver detalle y acciones."
            items={especialistas}
            columns={columns}
            createRoute={currentFolder?.id ? route('especialistas-consultoria.create', { folder_id: currentFolder.id }) : route('especialistas-consultoria.create')}
            editRoute={(id) => `/especialistas-consultoria/${id}/edit`}
            deleteRoute="especialistas-consultoria.destroy"
            filters={[]}
            routeParams={filters}
            userRole={userRole}
            folders={folders}
            currentFolder={currentFolder}
            breadcrumb={breadcrumb}
            storeFolderRoute="especialistas-consultoria.folders.store"
            indexRoute="especialistas-consultoria.index"
            indexTitle="Especialistas en Consultoría"
            operadores={operadores}
            renderDetail={renderDetail}
        />
    );
}
