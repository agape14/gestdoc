import React from 'react';
import ModuleIndex from '@/Components/ModuleIndex';

export default function Index({ bienes, filters, userRole, folders = [], currentFolder = null, breadcrumb = [], operadores = [] }) {
    const columns = [
        { header: 'ITEM / BIEN', accessor: 'titulo' },
        { header: 'ENTIDAD', accessor: 'entidad' },
        { header: 'COSTO', accessor: 'costo', render: (row) => `S/ ${parseFloat(row.costo || 0).toFixed(2)}` },
        { header: 'ESTADO', accessor: 'estado' },
        { header: 'ACCIONES', accessor: 'actions' }
    ];

    return (
        <ModuleIndex
            title="Proveedor de Bienes"
            description="Gestión de bienes públicos y privados"
            items={bienes}
            columns={columns}
            createRoute={currentFolder?.id ? route('proveedor-bienes.create', { folder_id: currentFolder.id }) : route('proveedor-bienes.create')}
            editRoute={(id) => route('proveedor-bienes.edit', id)}
            deleteRoute="proveedor-bienes.destroy"
            filters={[]}
            routeParams={filters}
            userRole={userRole}
            folders={folders}
            currentFolder={currentFolder}
            breadcrumb={breadcrumb}
            storeFolderRoute="proveedor-bienes.folders.store"
            indexRoute="proveedor-bienes.index"
            indexTitle="Proveedor de Bienes"
            operadores={operadores}
        />
    );
}
