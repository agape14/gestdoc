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
            title="Especialistas en Consultoría"
            description="Registro de profesionales y empresas de consultoría"
            items={especialistas}
            columns={columns}
            createRoute={route('especialistas-consultoria.create')}
            editRoute={(id) => route('especialistas-consultoria.edit', id)}
            deleteRoute="especialistas-consultoria.destroy"
            filters={filterOptions}
            routeParams={filters}
            userRole={userRole}
        />
    );
}
