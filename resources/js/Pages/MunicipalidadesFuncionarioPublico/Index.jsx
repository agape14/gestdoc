import React from 'react';
import ModuleIndex from '@/Components/ModuleIndex';
import ModuleIndexRowDetail from '@/Components/ModuleIndexRowDetail';
import ExperienciaPieTabla from '@/Components/ExperienciaPieTabla';
import { formatDateDisplay, formatMonedaPeruana } from '@/Utils/experienciaCalculations';

export default function Index({ especialistas, experienceTotals = {}, filters, userRole, folders = [], currentFolder = null, breadcrumb = [], operadores = [] }) {
    const estadoBadgeClass = (estado) => {
        const key = String(estado || '').toUpperCase();
        if (key === 'COMPLETO') return 'bg-success-subtle text-success-emphasis border border-success-subtle';
        if (key === 'INCOMPLETO') return 'bg-danger-subtle text-danger-emphasis border border-danger-subtle';
        if (key === 'ARCHIVADO') return 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle';
        return 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
    };
    const estadoLabel = (estado) => {
        const key = String(estado || '').toUpperCase();
        if (key === 'COMPLETO') return 'COMPLETO';
        if (key === 'INCOMPLETO') return 'INCOMPLETO';
        if (key === 'ARCHIVADO') return 'ARCHIVADO';
        return 'EN CURSO';
    };
    const labelTipo = (t) => ({ CONTRATO: 'Contrato', COMPROBANTE_DE_PAGO: 'Comprobante de pago', CONFORMIDAD_DE_SERVICIO: 'Conformidad de servicio' }[t] || t);

    const getDocumentLinks = (item) => {
        let docs = [];
        if (Array.isArray(item.documentos) && item.documentos.length > 0) {
            docs = item.documentos.map((d, idx) => ({
                label: d.nombre || `Documento ${idx + 1}`,
                path: d.file_path,
                url: d.url || null,
            })).filter((d) => d.path);
        } else if (item.archivo_contrato_url) {
            docs = [{ label: labelTipo(item.tipo_documento_adjunto) || 'Archivo', path: item.archivo_contrato, url: item.archivo_contrato_url }];
        } else if (item.documento) {
            docs = [{ label: 'Documento', path: item.documento, url: item.documento_url }];
        }
        if (item.archivo_suspension_url) docs.push({ label: 'PDF suspensión', path: item.archivo_suspension, url: item.archivo_suspension_url });
        if (item.archivo_reinicio_url) docs.push({ label: 'PDF reinicio', path: item.archivo_reinicio, url: item.archivo_reinicio_url });
        return docs;
    };

    const columns = [
        { header: 'N°', accessor: 'id', render: (item, rowIndex) => ((especialistas.current_page || 1) - 1) * (especialistas.per_page || 10) + (rowIndex ?? 0) + 1 },
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
        { header: 'CUI', accessor: 'cui' },
        { header: 'F. CONTRATO/CP', accessor: 'fecha_contrato_cp', render: (item) => formatDateDisplay(item.fecha_contrato_cp) },
        { header: 'FECHA INICIO', accessor: 'fecha_inicio', render: (item) => formatDateDisplay(item.fecha_inicio) },
        { header: 'FECHA CULM.', accessor: 'fecha_culminacion', render: (item) => formatDateDisplay(item.fecha_culminacion) },
        {
            header: 'ESTADO',
            accessor: 'estado',
            render: (item) => (
                <span className={`badge rounded-pill px-3 py-2 ${estadoBadgeClass(item.estado)}`}>
                    {estadoLabel(item.estado)}
                </span>
            ),
        },
        { header: 'T. DÍAS', accessor: 'total_dias', render: (item) => (item.total_dias != null && item.total_dias !== '' ? item.total_dias : '-') },
        { header: 'MONTO NETO', accessor: 'monto_neto', render: (item) => formatMonedaPeruana(item.monto_neto) },
        { header: '', accessor: '_expand', render: () => <i className="bi bi-chevron-down text-secondary" title="Ver detalle" /> },
    ];

    const getDetailFields = (item) => [
        { label: 'N° contrato / O/S / Comprobante', value: item.numero_contrato_os_comprobante },
        { label: 'Fecha contrato o CP', value: formatDateDisplay(item.fecha_contrato_cp) },
        { label: 'Estado', value: estadoLabel(item.estado) },
        { label: 'Fecha suspensión', value: formatDateDisplay(item.fecha_suspension) },
        { label: 'Fecha reinicio', value: formatDateDisplay(item.fecha_reinicio) },
        { label: 'Total meses', value: item.total_meses != null ? Number(item.total_meses).toFixed(2) : '' },
        { label: 'Total días', value: item.total_dias },
        { label: 'Monto acumulado', value: item.monto_acumulado != null ? formatMonedaPeruana(item.monto_acumulado) : '' },
        { label: 'Objeto del contrato (completo)', value: item.objeto_del_contrato, noTruncate: true },
    ];

    const exportExcelUrl = () => {
        const params = new URLSearchParams();
        if (currentFolder?.id) params.set('folder_id', currentFolder.id);
        if (filters?.user_id) params.set('user_id', filters.user_id);
        if (filters?.search) params.set('search', filters.search);
        if (filters?.tipo) params.set('tipo', filters.tipo);
        const qs = params.toString();
        return route('municipalidades-funcionario-publico.export') + (qs ? '?' + qs : '');
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
            editHref={`/municipalidades-funcionario-publico/${item.id}/edit`}
            deleteRouteName="municipalidades-funcionario-publico.destroy"
            documentButton={getDocumentLinks(item).length > 0 && context?.openDocumentsModal ? (
                <button type="button" className="btn btn-sm btn-outline-primary" onClick={(e) => { e.stopPropagation(); context.openDocumentsModal(item); }}>
                    <i className="bi bi-paperclip me-1"></i> Ver archivos
                </button>
            ) : null}
        />
    );

    return (
        <ModuleIndex
            renderFooter={<ExperienciaPieTabla totalDiasSinTraslape={experienceTotals.total_dias_sin_traslape ?? 0} totalMontoAcumulado={experienceTotals.total_monto_acumulado ?? 0} showMonto />}
            renderHeader={renderHeader}
            getDocumentLinks={getDocumentLinks}
            title="Municipalidades y/o Funcionario Publico"
            description="Experiencia registrada. Clic en la fila para ver detalle y acciones."
            items={especialistas}
            columns={columns}
            createRoute={currentFolder?.id ? route('municipalidades-funcionario-publico.create', { folder_id: currentFolder.id }) : route('municipalidades-funcionario-publico.create')}
            editRoute={(id) => `/municipalidades-funcionario-publico/${id}/edit`}
            deleteRoute="municipalidades-funcionario-publico.destroy"
            filters={[]}
            routeParams={filters}
            userRole={userRole}
            folders={folders}
            currentFolder={currentFolder}
            breadcrumb={breadcrumb}
            storeFolderRoute="municipalidades-funcionario-publico.folders.store"
            indexRoute="municipalidades-funcionario-publico.index"
            indexTitle="Municipalidades y/o Funcionario Publico"
            moveRouteName="municipalidades-funcionario-publico.move"
            moveBulkRouteName="municipalidades-funcionario-publico.move-bulk"
            operadores={operadores}
            renderDetail={renderDetail}
        />
    );
}
