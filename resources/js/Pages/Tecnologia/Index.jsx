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
            title="Tecnología"
            description="Recursos tecnológicos"
            items={items}
            columns={columns}
            createRoute={currentFolder?.id ? route('tecnologia.create', { folder_id: currentFolder.id }) : route('tecnologia.create')}
            editRoute={(id) => route('tecnologia.edit', id)}
            deleteRoute="tecnologia.destroy"
            filters={[]}
            routeParams={filters}
            userRole={userRole}
            folders={folders}
            currentFolder={currentFolder}
            breadcrumb={breadcrumb}
            indexRoute="tecnologia.index"
            indexTitle="Tecnología"
            getDocumentLinks={getDocumentLinks}
        />
    );
}
