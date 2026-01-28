import React from 'react';
import ModuleIndex from '@/Components/ModuleIndex';

export default function Index({ items, filters, userRole }) {
    const columns = [
        { header: 'PROYECTO', accessor: 'titulo' },
        { header: 'UBICACION', accessor: 'ubicacion' },
        { header: 'PRECIO', accessor: 'precio', render: (row) => `S/ ${parseFloat(row.precio || 0).toFixed(2)}` },
        { header: 'ESTADO', accessor: 'estado' },
        { header: 'ACCIONES', accessor: 'actions' }
    ];

    return (
        <ModuleIndex
            title="Inmobiliaria"
            description="Gestión de proyectos inmobiliarios"
            items={items}
            columns={columns}
            createRoute={route('inmobiliaria.create')}
            editRoute={(id) => route('inmobiliaria.edit', id)}
            deleteRoute="inmobiliaria.destroy"
            filters={filters}
            routeParams={filters}
            userRole={userRole}
        />
    );
}
