import React from 'react';
import ModuleIndex from '@/Components/ModuleIndex';

export default function Index({ items, filters, userRole, folders = [], currentFolder = null, breadcrumb = [] }) {
    const getDocumentLinks = (item) => (item.archivo ? [{ label: 'Documento', path: item.archivo }] : []);

    const columns = [
        { header: 'PLANTILLA', accessor: 'titulo' },
        { header: 'ESPECIALIDAD', accessor: 'especialidad' },
        { header: 'ACCIONES', accessor: 'actions' }
    ];

    return (
        <ModuleIndex
            title="Plantillas de Ingeniería"
            description="Repositorio de plantillas"
            items={items}
            columns={columns}
            createRoute={currentFolder?.id ? route('plantillas-ing.create', { folder_id: currentFolder.id }) : route('plantillas-ing.create')}
            editRoute={(id) => route('plantillas-ing.edit', id)}
            deleteRoute="plantillas-ing.destroy"
            filters={[]}
            routeParams={filters}
            userRole={userRole}
            folders={folders}
            currentFolder={currentFolder}
            breadcrumb={breadcrumb}
            indexRoute="plantillas-ing.index"
            indexTitle="Plantillas de Ingeniería"
            getDocumentLinks={getDocumentLinks}
        />
    );
}
