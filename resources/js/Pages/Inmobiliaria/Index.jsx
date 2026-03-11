import React from 'react';
import ModuleIndex from '@/Components/ModuleIndex';
import ModuleIndexRowDetail from '@/Components/ModuleIndexRowDetail';

export default function Index({ items, filters, userRole, folders = [], currentFolder = null, breadcrumb = [], operadores = [], anulados = [] }) {
    const columns = [
        { header: 'PROYECTO', accessor: 'titulo', render: (item) => (item.titulo ? String(item.titulo).slice(0, 45) + (String(item.titulo).length > 45 ? '…' : '') : '-') },
        { header: 'UBICACIÓN', accessor: 'ubicacion', render: (item) => (item.ubicacion ? String(item.ubicacion).slice(0, 35) + (String(item.ubicacion).length > 35 ? '…' : '') : '-') },
        { header: 'PRECIO', accessor: 'precio', render: (row) => `S/ ${parseFloat(row.precio || 0).toFixed(2)}` },
        { header: 'ESTADO', accessor: 'estado' },
        { header: '', accessor: '_expand', render: () => <i className="bi bi-chevron-down text-secondary" title="Ver detalle" /> },
    ];

    const getDetailFields = (item) => [
        { label: 'Proyecto (completo)', value: item.titulo },
        { label: 'Ubicación (completa)', value: item.ubicacion },
        { label: 'Precio', value: item.precio != null ? `S/ ${parseFloat(item.precio).toFixed(2)}` : '' },
        { label: 'Estado', value: item.estado },
    ];

    const renderDetail = (item) => (
        <ModuleIndexRowDetail
            item={item}
            userRole={userRole}
            fields={getDetailFields(item)}
            editHref={route('inmobiliaria.edit', item.id)}
            deleteRouteName="inmobiliaria.destroy"
        />
    );

    return (
        <ModuleIndex
            title="Inmobiliaria"
            description="Gestión de proyectos inmobiliarios. Clic en la fila para ver detalle y acciones."
            items={items}
            columns={columns}
            createRoute={currentFolder?.id ? route('inmobiliaria.create', { folder_id: currentFolder.id }) : route('inmobiliaria.create')}
            editRoute={(id) => route('inmobiliaria.edit', id)}
            deleteRoute="inmobiliaria.destroy"
            filters={[]}
            routeParams={filters}
            userRole={userRole}
            folders={folders}
            currentFolder={currentFolder}
            breadcrumb={breadcrumb}
            storeFolderRoute="inmobiliaria.folders.store"
            indexRoute="inmobiliaria.index"
            indexTitle="Inmobiliaria"
            moveRouteName="inmobiliaria.move"
            moveBulkRouteName="inmobiliaria.move-bulk"
            operadores={operadores}
            anulados={anulados}
            renderDetail={renderDetail}
        />
    );
}
