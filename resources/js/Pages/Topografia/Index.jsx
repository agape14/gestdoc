import React from 'react';
import ModuleIndex from '@/Components/ModuleIndex';

export default function Index({ items, filters, userRole, folders = [], currentFolder = null, breadcrumb = [] }) {
    const getDocumentLinks = (item) => (item.archivo ? [{ label: 'Documento', path: item.archivo }] : []);

    const columns = [
        { header: 'TÍTULO', accessor: 'titulo' },
        { header: 'DESCRIPCIÓN', accessor: 'descripcion' },
        { header: 'ACCIONES', accessor: 'actions' }
    ];

    return (
        <ModuleIndex
            title="Topografía"
            description="Documentos y recursos de topografía"
            items={items}
            columns={columns}
            createRoute={currentFolder?.id ? route('topografia.create', { folder_id: currentFolder.id }) : route('topografia.create')}
            editRoute={(id) => route('topografia.edit', id)}
            deleteRoute="topografia.destroy"
            filters={[]}
            routeParams={filters}
            userRole={userRole}
            folders={folders}
            currentFolder={currentFolder}
            breadcrumb={breadcrumb}
            indexRoute="topografia.index"
            indexTitle="Topografía"
            getDocumentLinks={getDocumentLinks}
        />
    );
}
