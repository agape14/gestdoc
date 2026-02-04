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
            title="Especialistas en Ejecución"
            description="Registro de profesionales y empresas de ejecución"
            items={especialistas}
            columns={columns}
            createRoute={currentFolder?.id ? route('especialistas-ejecucion.create', { folder_id: currentFolder.id }) : route('especialistas-ejecucion.create')}
            editRoute={(id) => route('especialistas-ejecucion.edit', id)}
            deleteRoute="especialistas-ejecucion.destroy"
            filters={[]}
            routeParams={filters}
            userRole={userRole}
            folders={folders}
            currentFolder={currentFolder}
            breadcrumb={breadcrumb}
            storeFolderRoute="especialistas-ejecucion.folders.store"
            indexRoute="especialistas-ejecucion.index"
            indexTitle="Especialistas en Ejecución"
            operadores={operadores}
        />
    );
}
