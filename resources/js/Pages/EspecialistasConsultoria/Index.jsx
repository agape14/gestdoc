import React from 'react';
import ModuleIndex from '@/Components/ModuleIndex';

export default function Index({ especialistas, filters, userRole, folders = [], currentFolder = null, breadcrumb = [], operadores = [] }) {
    const getDocumentLinks = (item) => (item.documento ? [{ label: 'Documento', path: item.documento }] : []);

    const columns = [
        { header: 'NOMBRE / RAZÓN SOCIAL', accessor: 'nombre' },
        { header: 'ESPECIALIDAD', accessor: 'especialidad' },
        { header: 'TIPO', accessor: 'tipo' },
        { header: 'ESTADO', accessor: 'estado' },
        { header: 'ACCIONES', accessor: 'actions' }
    ];

    return (
        <ModuleIndex
            getDocumentLinks={getDocumentLinks}
            title="Especialistas en Consultoría"
            description="Registro de profesionales y empresas de consultoría"
            items={especialistas}
            columns={columns}
            createRoute={currentFolder?.id ? route('especialistas-consultoria.create', { folder_id: currentFolder.id }) : route('especialistas-consultoria.create')}
            editRoute={(id) => route('especialistas-consultoria.edit', id)}
            deleteRoute="especialistas-consultoria.destroy"
            filters={[]}
            routeParams={filters}
            userRole={userRole}
            folders={folders}
            currentFolder={currentFolder}
            breadcrumb={breadcrumb}
            storeFolderRoute="especialistas-consultoria.folders.store"
            indexRoute="especialistas-consultoria.index"
            indexTitle="Especialistas en Consultoría"
            operadores={operadores}
        />
    );
}
