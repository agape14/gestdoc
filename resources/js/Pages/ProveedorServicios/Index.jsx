import React from 'react';
import ModuleIndex from '@/Components/ModuleIndex';
import ModuleIndexRowDetail from '@/Components/ModuleIndexRowDetail';
import ExperienciaPieTabla from '@/Components/ExperienciaPieTabla';
import { formatDateDisplay, formatMonedaPeruana } from '@/Utils/experienciaCalculations';

export default function Index({ servicios, experienceTotals = {}, filters, userRole, folders = [], currentFolder = null, breadcrumb = [], operadores = [] }) {
    const labelTipo = (t) => ({ CONTRATO: 'Contrato', COMPROBANTE_DE_PAGO: 'Comprobante de pago', CONFORMIDAD_DE_SERVICIO: 'Conformidad de servicio' }[t] || t);
    const getDocumentLinks = (item) => {
        const docs = Array.isArray(item.documentos)
            ? item.documentos.map((d, idx) => ({
                label: d.nombre || `Documento ${idx + 1}`,
                path: d.file_path,
                url: d.url || d.file_url || null,
            })).filter((d) => d.path)
            : [];
        if (item.archivo_suspension_url) {
            docs.push({ label: 'PDF suspensión', path: item.archivo_suspension, url: item.archivo_suspension_url });
        }
        if (item.archivo_reinicio_url) {
            docs.push({ label: 'PDF reinicio', path: item.archivo_reinicio, url: item.archivo_reinicio_url });
        }
        if (docs.length > 0) return docs;
        if (item.archivo_contrato_url) {
            return [{ label: labelTipo(item.tipo_documento_adjunto) || 'Archivo', path: item.archivo_contrato, url: item.archivo_contrato_url }];
        }
        return [];
    };

    const columns = [
        { header: 'N°', accessor: 'id', render: (item, rowIndex) => ((servicios.current_page || 1) - 1) * (servicios.per_page || 10) + (rowIndex ?? 0) + 1 },
        { header: 'CLIENTE', accessor: 'cliente', render: (item) => (item.cliente ? String(item.cliente).slice(0, 35) + (String(item.cliente).length > 35 ? '…' : '') : '-') },
        {
            header: 'OBJETO',
            accessor: 'objeto_del_contrato',
            render: (item) => (
                <span className="d-inline-block text-wrap" style={{ maxWidth: '320px', minWidth: '260px', whiteSpace: 'normal', wordBreak: 'break-word' }}>
                    {item.objeto_del_contrato || '-'}
                </span>
            ),
        },
        { header: 'FECHA INICIO', accessor: 'fecha_inicio', render: (item) => formatDateDisplay(item.fecha_inicio) },
        { header: 'FECHA CULM.', accessor: 'fecha_culminacion', render: (item) => formatDateDisplay(item.fecha_culminacion) },
        { header: 'TOTAL DÍAS', accessor: 'total_dias', render: (item) => item.total_dias ?? '-' },
        { header: 'MONTO NETO', accessor: 'monto_neto', render: (item) => formatMonedaPeruana(item.monto_neto) },
        { header: '', accessor: '_expand', render: () => <i className="bi bi-chevron-down text-secondary" title="Ver detalle" /> },
    ];

    const getDetailFields = (item) => {
        const nombresDocumentos = Array.isArray(item.documentos) && item.documentos.length > 0
            ? item.documentos.map((d) => d.nombre).filter(Boolean).join(', ')
            : '';
        return [
            { label: 'Cliente', value: item.cliente },
            { label: 'N° contrato / O/S / Comprobante', value: item.numero_contrato_os_comprobante },
            { label: 'Fecha suspensión', value: formatDateDisplay(item.fecha_suspension) },
            { label: 'Fecha reinicio', value: formatDateDisplay(item.fecha_reinicio) },
            { label: 'Total meses', value: item.total_meses != null ? Number(item.total_meses).toFixed(2) : '' },
            { label: 'Total días', value: item.total_dias },
            { label: 'Monto neto', value: item.monto_neto != null ? formatMonedaPeruana(item.monto_neto) : '' },
            { label: 'Monto acumulado', value: item.monto_acumulado != null ? formatMonedaPeruana(item.monto_acumulado) : '' },
            { label: 'Documentos adjuntos', value: nombresDocumentos || 'Sin documentos' },
            { label: 'Objeto del contrato (completo)', value: item.objeto_del_contrato, noTruncate: true },
        ];
    };

    const exportExcelUrl = () => {
        const params = new URLSearchParams();
        if (currentFolder?.id) params.set('folder_id', currentFolder.id);
        if (filters?.user_id) params.set('user_id', filters.user_id);
        if (filters?.search) params.set('search', filters.search);
        if (filters?.especialidad) params.set('especialidad', filters.especialidad);
        if (filters?.tipo) params.set('tipo', filters.tipo);
        const qs = params.toString();
        return route('proveedor-servicios.export') + (qs ? '?' + qs : '');
    };

    const renderHeader = (
        <a href={exportExcelUrl()} className="btn btn-success shadow-sm rounded-pill px-4" target="_blank" rel="noopener noreferrer">
            <i className="bi bi-file-earmark-excel me-2"></i> Exportar Excel
        </a>
    );

    const renderDetail = (item, context) => (
        <ModuleIndexRowDetail
            item={item}
            userRole={userRole}
            fields={getDetailFields(item)}
            truncateLongText={false}
            editHref={`/proveedor-servicios/${item.id}/edit`}
            deleteRouteName="proveedor-servicios.destroy"
            documentButton={getDocumentLinks(item).length > 0 && context?.openDocumentsModal ? (
                <button type="button" className="btn btn-sm btn-outline-primary" onClick={(e) => { e.stopPropagation(); context.openDocumentsModal(item); }}>
                    <i className="bi bi-paperclip me-1"></i> Ver archivos
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
            title="Proveedor de Servicios"
            description="Experiencia en la especialidad. Clic en la fila para ver detalle y acciones."
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
            moveRouteName="proveedor-servicios.move"
            moveBulkRouteName="proveedor-servicios.move-bulk"
            operadores={operadores}
            renderDetail={renderDetail}
            renderHeader={renderHeader}
        />
    );
}
