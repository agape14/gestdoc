import React from 'react';
import ModuleIndex from '@/Components/ModuleIndex';

export default function Index({ especialistas, filters, userRole }) {
    const columns = [
        { header: 'NOMBRE / RAZÓN SOCIAL', accessor: 'nombre' },
        { header: 'ESPECIALIDAD', accessor: 'especialidad' },
        { header: 'TIPO', accessor: 'tipo' },
        { header: 'ESTADO', accessor: 'estado' },
        { header: 'ACCIONES', accessor: 'actions' }
    ];

    const filterOptions = [
        { key: 'tipo', value: 'Profesional', label: 'PROFESIONALES', icon: 'bi-person' },
        { key: 'tipo', value: 'Empresa', label: 'EMPRESAS', icon: 'bi-building' }
    ];

    return (
        <ModuleIndex
            title="Especialistas en Ejecución"
            description="Registro de profesionales y empresas de ejecución"
            items={especialistas}
            columns={columns}
            createRoute={route('especialistas-ejecucion.create')}
            editRoute={(id) => route('especialistas-ejecucion.edit', id)}
            deleteRoute="especialistas-ejecucion.destroy"
            filters={filterOptions}
            routeParams={filters}
            userRole={userRole}
        />
    );
}
