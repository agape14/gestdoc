import React from 'react';
import ModuleIndex from '@/Components/ModuleIndex';

export default function Index({ bienes, filters, userRole }) {
    const columns = [
        { header: 'ITEM / BIEN', accessor: 'titulo' },
        { header: 'ENTIDAD', accessor: 'entidad' },
        { header: 'COSTO', accessor: 'costo', render: (row) => `S/ ${parseFloat(row.costo || 0).toFixed(2)}` },
        { header: 'ESTADO', accessor: 'estado' },
        { header: 'ACCIONES', accessor: 'actions' }
    ];

    const filterOptions = [
        { key: 'tipo', value: 'Publica', label: 'PÚBLICAS', icon: 'bi-building' },
        { key: 'tipo', value: 'Privada', label: 'PRIVADAS', icon: 'bi-shield-lock' }
    ];

    return (
        <ModuleIndex
            title="Proveedor de Bienes"
            description="Gestión de bienes públicos y privados"
            items={bienes}
            columns={columns}
            createRoute={route('proveedor-bienes.create')}
            editRoute={(id) => route('proveedor-bienes.edit', id)}
            deleteRoute="proveedor-bienes.destroy"
            filters={filterOptions}
            routeParams={filters}
            userRole={userRole}
        />
    );
}
