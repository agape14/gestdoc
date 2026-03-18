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
};

const ACCIONES_CREAR = [
    { key: 'ADICIONAL', label: 'ADICIONAL', title: 'Nuevo registro: ADICIONAL', btnClass: 'btn-success' },
    { key: 'ADICIONAL_CON_DEDUCTIVO', label: 'ADIC. + DED.', title: 'Nuevo registro: ADICIONAL CON DEDUCTIVO', btnClass: 'btn-warning text-dark' },
    { key: 'DEDUCTIVO', label: 'DEDUCTIVO', title: 'Nuevo registro: DEDUCTIVO', btnClass: 'btn-danger' },
    { key: 'ACTUALIZACION_PRECIOS', label: 'ACT. PRECIOS', title: 'Nuevo registro: ACTUALIZACIÓN DE PRECIOS', btnClass: 'btn-info text-dark' },
    { key: 'REFORMULACION', label: 'REFORMULACIÓN', title: 'Nuevo registro: REFORMULACIÓN', btnClass: 'btn-dark' },
];

export default function Index({ expedientes, filters = {}, userRole, folders = [], moveTargetFolders = null, currentFolder = null, breadcrumb = [], operadores = [] }) {
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

    const columns = [
        { header: 'ETIQUETA', accessor: 'etiqueta', render: (item) => item.etiqueta ?? '-' },
        { header: 'TIPO INVERSIÓN', accessor: 'tipo_inversion', render: (item) => (item.tipo_inversion ? String(item.tipo_inversion).slice(0, 35) + (String(item.tipo_inversion).length > 35 ? '…' : '') : '-') },
        { header: 'PROYECTO', accessor: 'proyecto', render: (item) => (item.proyecto ? String(item.proyecto).slice(0, 50) + (String(item.proyecto).length > 50 ? '…' : '') : '-') },
        { header: 'CUI', accessor: 'cui' },
        { header: 'FECHA APROB.', accessor: 'fecha_aprobacion', render: (item) => formatDateDisplay(item.fecha_aprobacion) },
        { header: 'MONTOS', accessor: 'monto_total', render: (item) => formatMonedaPeruana(item.monto_total) },
        { header: '', accessor: '_expand', render: () => <i className="bi bi-chevron-down text-secondary" title="Ver detalle" /> },
    ];

    const getDetailFields = (item) => {
        const accion = item.tipo_accion ? (TIPO_ACCION_LABEL[item.tipo_accion] || item.tipo_accion) : null;
        const fields = [
            { label: 'Tipo de acción', value: accion },
            { label: 'Descripción', value: item.descripcion },
            { label: 'N° de folio', value: item.numero_folio },
            { label: 'Tomos', value: item.tomos },
            { label: 'Año', value: item.anio },
            { label: 'Tipo unidad conservación', value: item.tipo_unidad_conservacion },
            { label: 'Resolución', value: item.resolucion },
            { label: 'Contrato', value: item.contrato ? 'Sí (archivo cargado)' : 'No' },
            { label: 'Resolución (archivo)', value: item.resolucion_archivo ? 'Sí (archivo cargado)' : 'No' },
            { label: 'Proyecto (completo)', value: item.proyecto },
        ];
        return fields.filter((f) => f.value != null && f.value !== '');
    };

    const extraActionsFor = (item) =>
        ACCIONES_CREAR.map((a) => ({
            href: hrefCrearDesdeFila(item, a.key),
            label: a.label,
            title: a.title,
            btnClass: a.btnClass,
        }));

    const renderDetail = (item) => (
        <ModuleIndexRowDetail
            item={item}
            userRole={userRole}
            fields={getDetailFields(item)}
            editHref={`/registro-expedientes/${item.id}/edit`}
            onDelete={handleDelete}
            extraActions={extraActionsFor(item)}
        />
    );

    const exportExcelUrl = () => {
        const params = new URLSearchParams();
        if (currentFolder?.id) params.set('folder_id', currentFolder.id);
        if (filters?.user_id) params.set('user_id', filters.user_id);
        if (filters?.search) params.set('search', filters.search);
        const qs = params.toString();
        return route('registro-expedientes.export') + (qs ? '?' + qs : '');
    };

    const renderHeader = (
        <div className="d-flex justify-content-end mb-2">
            <a href={exportExcelUrl()} className="btn btn-success shadow-sm rounded-pill px-4" target="_blank" rel="noopener noreferrer">
                <i className="bi bi-file-earmark-excel me-2"></i> Exportar Excel
            </a>
        </div>
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
        />
    );
}
