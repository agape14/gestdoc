import React from 'react';
import { router } from '@inertiajs/react';
import Swal from 'sweetalert2';
import ModuleIndex from '@/Components/ModuleIndex';
import ModuleIndexRowDetail from '@/Components/ModuleIndexRowDetail';
import { formatDateDisplay, formatMonedaPeruana } from '@/Utils/experienciaCalculations';

const TIPO_ACCION_LABEL = {
    ADICIONAL: 'ADICIONAL',
    ADICIONAL_CON_DEDUCTIVO: 'ADICIONAL CON DEDUCTIVO',
    DEDUCTIVO: 'DEDUCTIVO',
    ACTUALIZACION_PRECIOS: 'ACTUALIZACIÓN DE PRECIOS',
    REFORMULACION: 'REFORMULACIÓN',
    VALORIZACION: 'VALORIZACIÓN',
    LIQUIDACION: 'LIQUIDACIÓN',
};

const ACCIONES_CREAR = [
    { key: 'ADICIONAL', label: 'ADICIONAL', title: 'Nuevo registro: ADICIONAL', btnClass: 'btn-success' },
    { key: 'ADICIONAL_CON_DEDUCTIVO', label: 'ADIC. + DED.', title: 'Nuevo registro: ADICIONAL CON DEDUCTIVO', btnClass: 'btn-warning text-dark' },
    { key: 'DEDUCTIVO', label: 'DEDUCTIVO', title: 'Nuevo registro: DEDUCTIVO', btnClass: 'btn-danger' },
    { key: 'ACTUALIZACION_PRECIOS', label: 'ACT. PRECIOS', title: 'Nuevo registro: ACTUALIZACIÓN DE PRECIOS', btnClass: 'btn-info text-dark' },
    { key: 'REFORMULACION', label: 'REFORMULACIÓN', title: 'Nuevo registro: REFORMULACIÓN', btnClass: 'btn-dark' },
    { key: 'VALORIZACION', label: 'VALORIZACIÓN', title: 'Nuevo registro: VALORIZACIÓN', btnClass: 'btn-primary' },
    { key: 'LIQUIDACION', label: 'LIQUIDACIÓN', title: 'Nuevo registro: LIQUIDACIÓN', btnClass: 'btn-secondary' },
];

const ESTADOS = ['EN CURSO', 'SOLO EXPEDIENTE', 'PROCESO DE EJECUCION', 'ARCHIVADO'];

const estadoBadgeClass = (estado) => {
    switch (String(estado || '').toUpperCase()) {
        case 'SOLO EXPEDIENTE':
            return 'text-bg-info';
        case 'PROCESO DE EJECUCION':
            return 'text-bg-primary';
        case 'ARCHIVADO':
            return 'text-bg-secondary';
        default:
            return 'text-bg-warning';
    }
};

const fileBasename = (path) => {
    if (!path) return '';
    const parts = String(path).replace(/\\/g, '/').split('/');
    return parts[parts.length - 1] || '';
};

export default function Index({ expedientes, filters = {}, userRole, folders = [], moveTargetFolders = null, currentFolder = null, breadcrumb = [], operadores = [] }) {
    const sortBy = filters?.sort_by || 'etiqueta';
    const sortDir = filters?.sort_dir || 'asc';

    const toggleSort = (field) => {
        const nextDir = sortBy === field && sortDir === 'asc' ? 'desc' : 'asc';
        const params = {
            ...filters,
            sort_by: field,
            sort_dir: nextDir,
        };
        router.get(route('registro-expedientes.index'), params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const sortHeader = (label, field) => (
        <button
            type="button"
            className="btn btn-link btn-sm text-decoration-none p-0 fw-semibold text-uppercase"
            onClick={(e) => {
                e.stopPropagation();
                toggleSort(field);
            }}
            title={`Ordenar por ${label}`}
        >
            {label}{' '}
            {sortBy === field ? (
                <i className={`bi ${sortDir === 'asc' ? 'bi-sort-down-alt' : 'bi-sort-up-alt'} ms-1`} />
            ) : (
                <i className="bi bi-arrow-down-up ms-1 text-secondary" />
            )}
        </button>
    );

    const getEstado = (item) => {
        const estado = String(item?.estado || '').toUpperCase();
        if (ESTADOS.includes(estado)) return estado;
        return item?.tipo_accion === 'LIQUIDACION' ? 'ARCHIVADO' : 'EN CURSO';
    };

    const handleDelete = (item) => {
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'No podrás revertir esta acción.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('registro-expedientes.destroy', item.id));
            }
        });
    };

    const hrefCrearDesdeFila = (item, tipoAccion) => {
        const p = new URLSearchParams();
        if (currentFolder?.id) p.set('folder_id', String(currentFolder.id));
        p.set('prefill_from', String(item.id));
        p.set('tipo_accion', tipoAccion);
        return route('registro-expedientes.create') + (p.toString() ? `?${p.toString()}` : '');
    };

    const getDocumentLinks = (item) => {
        const docs = [];
        if (item.contrato || item.contrato_url) {
            docs.push({
                label: 'Contrato',
                path: item.contrato || '',
                url: item.contrato_url || null,
                filename: fileBasename(item.contrato),
            });
        }
        if (item.resolucion_archivo || item.resolucion_archivo_url) {
            docs.push({
                label: 'Resolución',
                path: item.resolucion_archivo || '',
                url: item.resolucion_archivo_url || null,
                filename: fileBasename(item.resolucion_archivo),
            });
        }
        return docs;
    };

    const columns = [
        { header: sortHeader('ETIQUETA', 'etiqueta'), accessor: 'etiqueta', render: (item) => item.etiqueta ?? '-' },
        { header: sortHeader('TIPO INVERSIÓN', 'tipo_inversion'), accessor: 'tipo_inversion', render: (item) => item.tipo_inversion || '-' },
        {
            header: sortHeader('PROYECTO', 'proyecto'),
            accessor: 'proyecto',
            render: (item) => (
                <span
                    className="d-inline-block text-wrap"
                    style={{ maxWidth: '320px', minWidth: '260px', whiteSpace: 'normal', wordBreak: 'break-word' }}
                >
                    {item.proyecto || '-'}
                </span>
            ),
        },
        { header: sortHeader('CUI', 'cui'), accessor: 'cui' },
        {
            header: sortHeader('ESTADO', 'estado'),
            accessor: 'estado',
            render: (item) => {
                const estado = getEstado(item);
                return (
                    <span className={`badge rounded-pill px-3 py-2 ${estadoBadgeClass(estado)}`}>
                        {estado}
                    </span>
                );
            },
        },
        {
            header: 'DESCRIPCIÓN',
            accessor: 'descripcion',
            render: (item) => (
                <span
                    className="d-inline-block text-wrap"
                    style={{ maxWidth: '200px', minWidth: '140px', whiteSpace: 'normal', wordBreak: 'break-word' }}
                >
                    {item.descripcion || '-'}
                </span>
            ),
        },
        { header: sortHeader('FECHA APROB.', 'fecha_aprobacion'), accessor: 'fecha_aprobacion', render: (item) => formatDateDisplay(item.fecha_aprobacion) },
        { header: sortHeader('MONTOS', 'monto_total'), accessor: 'monto_total', render: (item) => formatMonedaPeruana(item.monto_total) },
        { header: '', accessor: '_expand', render: () => <i className="bi bi-chevron-down text-secondary" title="Ver detalle" /> },
    ];

    const archivoField = (item, context, label, path, url) => {
        if (!path && !url) return null;
        const name = fileBasename(path) || 'Ver documento';
        const isPdf = /\.pdf(\?|$)/i.test(String(path || '')) || /\.pdf(\?|$)/i.test(String(url || ''));
        return {
            label,
            noTruncate: true,
            value: (
                <button
                    type="button"
                    className="btn btn-link btn-sm p-0 align-baseline text-decoration-none"
                    onClick={(e) => {
                        e.stopPropagation();
                        if (isPdf && context?.openPdfInModal) {
                            context.openPdfInModal(label, path, url);
                        } else if (context?.openDocumentsModal) {
                            context.openDocumentsModal(item);
                        } else if (url) {
                            window.open(url, '_blank', 'noopener,noreferrer');
                        }
                    }}
                    title="Ver documento"
                >
                    <i className="bi bi-eye me-1"></i>
                    {name}
                </button>
            ),
        };
    };

    const getDetailFields = (item, context = {}) => {
        const accion = item.tipo_accion ? (TIPO_ACCION_LABEL[item.tipo_accion] || item.tipo_accion) : null;
        const fields = [
            { label: 'Estado', value: getEstado(item) },
            { label: 'Tipo de acción', value: accion },
            { label: 'N° de folio', value: item.numero_folio },
            { label: 'Tomos', value: item.tomos },
            { label: 'Año', value: item.anio },
            { label: 'Tipo unidad conservación', value: item.tipo_unidad_conservacion },
            { label: 'Resolución', value: item.resolucion },
            archivoField(item, context, 'Contrato (archivo)', item.contrato, item.contrato_url),
            archivoField(item, context, 'Resolución (archivo)', item.resolucion_archivo, item.resolucion_archivo_url),
            { label: 'EXPEDIENTE TECNICO (S/)', value: formatMonedaPeruana(item.monto_o ?? 0) },
            { label: 'EVALUACION (S/)', value: formatMonedaPeruana(item.monto_p ?? 0) },
            { label: 'PPTO DE OBRA (S/)', value: formatMonedaPeruana(item.monto_s ?? 0) },
            { label: 'SUPERVISION (S/)', value: formatMonedaPeruana(item.monto_supervision ?? 0) },
            { label: 'Total montos', value: formatMonedaPeruana(item.monto_total ?? 0) },
            { label: 'Proyecto (completo)', value: item.proyecto },
        ];
        return fields.filter((f) => f && f.value != null && f.value !== '');
    };

    const extraActionsFor = (item) =>
        ACCIONES_CREAR.map((a) => ({
            href: hrefCrearDesdeFila(item, a.key),
            label: a.label,
            title: a.title,
            btnClass: a.btnClass,
        }));

    const renderDetail = (item, context) => (
        <ModuleIndexRowDetail
            item={item}
            userRole={userRole}
            fields={getDetailFields(item, context)}
            editHref={`/registro-expedientes/${item.id}/edit`}
            onDelete={handleDelete}
            extraActions={extraActionsFor(item)}
            documentButton={getDocumentLinks(item).length > 0 && context?.openDocumentsModal ? (
                <button type="button" className="btn btn-sm btn-outline-primary" onClick={(e) => { e.stopPropagation(); context.openDocumentsModal(item); }}>
                    <i className="bi bi-paperclip me-1"></i> Ver archivos
                </button>
            ) : null}
        />
    );

    const exportExcelUrl = () => {
        const params = new URLSearchParams();
        if (currentFolder?.id) params.set('folder_id', currentFolder.id);
        if (filters?.user_id) params.set('user_id', filters.user_id);
        if (filters?.search) params.set('search', filters.search);
        if (filters?.sort_by) params.set('sort_by', filters.sort_by);
        if (filters?.sort_dir) params.set('sort_dir', filters.sort_dir);
        if (filters?.per_page) params.set('per_page', filters.per_page);
        const qs = params.toString();
        return route('registro-expedientes.export') + (qs ? '?' + qs : '');
    };

    const renderHeader = (
        <a href={exportExcelUrl()} className="btn btn-success shadow-sm rounded-pill px-4" target="_blank" rel="noopener noreferrer">
            <i className="bi bi-file-earmark-excel me-2"></i> Exportar Excel
        </a>
    );

    return (
        <ModuleIndex
            title="Registro de Expedientes"
            description="Expedientes técnicos y proyectos. Clic en la fila para ver detalle y acciones."
            items={expedientes}
            columns={columns}
            createRoute={currentFolder?.id ? route('registro-expedientes.create', { folder_id: currentFolder.id }) : route('registro-expedientes.create')}
            editRoute={(id) => `/registro-expedientes/${id}/edit`}
            deleteRoute="registro-expedientes.destroy"
            filters={[]}
            routeParams={filters}
            userRole={userRole}
            folders={folders}
            moveTargetFolders={moveTargetFolders}
            currentFolder={currentFolder}
            breadcrumb={breadcrumb}
            storeFolderRoute="registro-expedientes.folders.store"
            indexRoute="registro-expedientes.index"
            indexTitle="Registro de Expedientes"
            moveRouteName="registro-expedientes.move"
            moveBulkRouteName="registro-expedientes.move-bulk"
            operadores={operadores}
            anulados={null}
            renderDetail={renderDetail}
            renderHeader={renderHeader}
            getDocumentLinks={getDocumentLinks}
        />
    );
}
