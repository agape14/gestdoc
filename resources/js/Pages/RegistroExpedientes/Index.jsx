import React from 'react';
import { router } from '@inertiajs/react';
import Swal from 'sweetalert2';
import ModuleIndex from '@/Components/ModuleIndex';
import ModuleIndexRowDetail from '@/Components/ModuleIndexRowDetail';
import { formatDateDisplay, formatMonedaPeruana } from '@/Utils/experienciaCalculations';

export default function Index({ expedientes, filters = {}, userRole, folders = [], currentFolder = null, breadcrumb = [], operadores = [] }) {
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
        { header: 'N°', accessor: 'numero', render: (item) => item.numero ?? '-' },
        { header: 'TIPO INVERSIÓN', accessor: 'tipo_inversion', render: (item) => (item.tipo_inversion ? String(item.tipo_inversion).slice(0, 35) + (String(item.tipo_inversion).length > 35 ? '…' : '') : '-') },
        { header: 'PROYECTO', accessor: 'proyecto', render: (item) => (item.proyecto ? String(item.proyecto).slice(0, 50) + (String(item.proyecto).length > 50 ? '…' : '') : '-') },
        { header: 'CUI', accessor: 'cui' },
        { header: 'FECHA APROB.', accessor: 'fecha_aprobacion', render: (item) => formatDateDisplay(item.fecha_aprobacion) },
        { header: 'MONTOS', accessor: 'monto_total', render: (item) => formatMonedaPeruana(item.monto_total) },
        { header: '', accessor: '_expand', render: () => <i className="bi bi-chevron-down text-secondary" title="Ver detalle" /> },
    ];

    const getDetailFields = (item) => [
        { label: 'N°', value: item.numero },
        { label: 'Etiqueta', value: item.etiqueta },
        { label: '¿Tiene actualización de precios?', value: item.tiene_actualizacion_precios || '-' },
        { label: '¿Tiene reformulación?', value: item.tiene_reformulacion || '-' },
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
        />
    );
}
