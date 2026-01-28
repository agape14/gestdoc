import React from 'react';
import ModuleIndex from '@/Components/ModuleIndex';

export default function Index({ items, filters, userRole }) {
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
            createRoute={route('topografia.create')}
            editRoute={(id) => route('topografia.edit', id)}
            deleteRoute="topografia.destroy"
            filters={filters}
            routeParams={filters}
            userRole={userRole}
        />
    );
}
