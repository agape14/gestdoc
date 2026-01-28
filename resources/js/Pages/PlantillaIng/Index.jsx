import React from 'react';
import ModuleIndex from '@/Components/ModuleIndex';

export default function Index({ items, filters, userRole }) {
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
            createRoute={route('plantillas-ing.create')}
            editRoute={(id) => route('plantillas-ing.edit', id)}
            deleteRoute="plantillas-ing.destroy"
            filters={filters}
            routeParams={filters}
            userRole={userRole}
        />
    );
}
