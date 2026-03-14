import React from 'react';
import { router } from '@inertiajs/react';
import Swal from 'sweetalert2';
import ModuleIndex from '@/Components/ModuleIndex';
import ModuleIndexRowDetail from '@/Components/ModuleIndexRowDetail';
import { formatDateDisplay, formatMonedaPeruana } from '@/Utils/experienciaCalculations';

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

    const columns = [
        { header: 'ETIQUETA', accessor: 'etiqueta', render: (item) => item.etiqueta ?? '-' },
        { header: 'TIPO INVERSIÓN', accessor: 'tipo_inversion', render: (item) => (item.tipo_inversion ? String(item.tipo_inversion).slice(0, 35) + (String(item.tipo_inversion).length > 35 ? '…' : '') : '-') },
        { header: 'PROYECTO', accessor: 'proyecto', render: (item) => (item.proyecto ? String(item.proyecto).slice(0, 50) + (String(item.proyecto).length > 50 ? '…' : '') : '-') },
        { header: 'CUI', accessor: 'cui' },
        { header: 'FECHA APROB.', accessor: 'fecha_aprobacion', render: (item) => formatDateDisplay(item.fecha_aprobacion) },
        { header: 'MONTOS', accessor: 'monto_total', render: (item) => formatMonedaPeruana(item.monto_total) },
        { header: '', accessor: '_expand', render: () => <i className="bi bi-chevron-down text-secondary" title="Ver detalle" /> },
    ];

    const getDetailFields = (item) => [
        { label: 'Etiqueta', value: item.etiqueta },
        { label: '¿Tiene actualización de precios?', value: item.tiene_actualizacion_precios || '-' },
        { label: '¿Tiene reformulación?', value: item.tiene_reformulacion || '-' },
        { label: '¿Tuvo suspensión?', value: item.tuvo_suspension || '-' },
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

    const renderDetail = (item) => (
        <ModuleIndexRowDetail
            item={item}
            userRole={userRole}
            fields={getDetailFields(item)}
            editHref={`/registro-expedientes/${item.id}/edit`}
            onDelete={handleDelete}
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
