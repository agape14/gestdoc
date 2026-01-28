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
            title="Tecnología"
            description="Recursos tecnológicos"
            items={items}
            columns={columns}
            createRoute={route('tecnologia.create')}
            editRoute={(id) => route('tecnologia.edit', id)}
            deleteRoute="tecnologia.destroy"
            filters={filters}
            routeParams={filters}
            userRole={userRole}
        />
    );
}
